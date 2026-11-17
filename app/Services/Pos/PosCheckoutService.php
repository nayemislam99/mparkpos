<?php

namespace App\Services\Pos;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCheckoutService
{
    public function checkout(
        User $user,
        Company $company,
        Branch $branch,
        array $data,
    ): array {
        return DB::transaction(function () use ($user, $company, $branch, $data): array {
            DB::table('companies')->where('id', $company->id)->lockForUpdate()->first();

            $customer = null;
            if (! empty($data['customer_id'])) {
                $customer = DB::table('customers')
                    ->where('company_id', $company->id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where('id', $data['customer_id'])
                    ->first();

                if (! $customer) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'Select an active customer from this store.',
                    ]);
                }
            }

            $productIds = collect($data['items'])->pluck('product_id')->unique()->sort()->values();
            $products = DB::table('products')
                ->where('company_id', $company->id)
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more products are no longer available in this store.',
                ]);
            }

            $stocks = DB::table('branch_stocks')
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $items = [];
            $subtotal = 0.0;
            $lineDiscountTotal = 0.0;

            foreach ($data['items'] as $input) {
                $product = $products->get($input['product_id']);
                $quantity = round((float) $input['quantity'], 3);
                $unitPrice = (float) $product->sale_price;
                $baseTotal = round($quantity * $unitPrice, 2);
                $discountType = $input['discount_type'] ?? 'flat';
                $discountValue = round((float) ($input['discount_value'] ?? 0), 2);

                $lineDiscount = $discountType === 'percent'
                    ? round($baseTotal * $discountValue / 100, 2)
                    : min($discountValue, $baseTotal);
                $lineTotal = max(0, round($baseTotal - $lineDiscount, 2));
                $subtotal += $baseTotal;
                $lineDiscountTotal += $lineDiscount;

                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_total' => $lineDiscount,
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $lineDiscountTotal = round($lineDiscountTotal, 2);
            $discountableTotal = max(0, $subtotal - $lineDiscountTotal);
            $orderDiscountType = $data['order_discount_type'] ?? 'flat';
            $orderDiscountValue = round((float) ($data['order_discount_value'] ?? 0), 2);
            $orderDiscountTotal = $orderDiscountType === 'percent'
                ? round($discountableTotal * $orderDiscountValue / 100, 2)
                : min($orderDiscountValue, $discountableTotal);
            $grandTotal = max(0, round($discountableTotal - $orderDiscountTotal, 2));

            if ($grandTotal <= 0) {
                throw ValidationException::withMessages([
                    'order_discount_value' => 'The sale total must be greater than zero.',
                ]);
            }

            $legacyPayment = $data['payment'] ?? [];
            $payments = $data['payments'] ?? (
                (float) ($legacyPayment['amount'] ?? 0) > 0
                    ? [$legacyPayment]
                    : []
            );
            $payments = array_values(array_filter(array_map(
                static fn (array $payment): array => [
                    'method' => $payment['method'],
                    'amount' => round((float) $payment['amount'], 2),
                    'reference' => $payment['reference'] ?? null,
                ],
                $payments,
            ), static fn (array $payment): bool => $payment['amount'] > 0));
            $tendered = round(array_sum(array_column($payments, 'amount')), 2);
            $paidTotal = min($tendered, $grandTotal);
            $dueTotal = round($grandTotal - $paidTotal, 2);

            if (! $customer && $dueTotal > 0) {
                throw ValidationException::withMessages([
                    (isset($data['payments']) ? 'payments' : 'payment.amount') => 'Walk-in sales must be paid in full.',
                ]);
            }

            $cashTendered = array_sum(array_map(
                static fn (array $payment): float => $payment['method'] === 'cash' ? $payment['amount'] : 0,
                $payments,
            ));
            $change = max(0, round($tendered - $grandTotal, 2));
            if ($change > $cashTendered) {
                throw ValidationException::withMessages([
                    (isset($data['payments']) ? 'payments' : 'payment.amount') => 'Overpayment must be covered by cash.',
                ]);
            }

            $invoiceNumber = $this->nextInvoiceNumber($company);
            $now = now();
            $saleId = DB::table('sales')->insertGetId([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'customer_id' => $customer?->id,
                'created_by' => $user->id,
                'invoice_number' => $invoiceNumber,
                'customer_name' => $customer?->name ?? 'Walk-in customer',
                'customer_phone' => $customer?->phone,
                'subtotal' => $subtotal,
                'line_discount_total' => $lineDiscountTotal,
                'order_discount_type' => $orderDiscountType,
                'order_discount_value' => $orderDiscountValue,
                'order_discount_total' => $orderDiscountTotal,
                'grand_total' => $grandTotal,
                'paid_total' => $paidTotal,
                'due_total' => $dueTotal,
                'payment_status' => $this->paymentStatus($paidTotal, $dueTotal),
                'status' => 'completed',
                'note' => $data['note'] ?? null,
                'sold_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                DB::table('sale_items')->insert([
                    'company_id' => $company->id,
                    'sale_id' => $saleId,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'unit' => $product->unit,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_type' => $item['discount_type'],
                    'discount_value' => $item['discount_value'],
                    'discount_total' => $item['discount_total'],
                    'line_total' => $item['line_total'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($product->track_stock) {
                    $stock = $stocks->get($product->id);
                    $available = (float) ($stock->quantity ?? 0);

                    if ($available < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => "{$product->name} has only {$available} {$product->unit} in stock.",
                        ]);
                    }

                    $remaining = round($available - $item['quantity'], 3);
                    if ($stock) {
                        DB::table('branch_stocks')->where('id', $stock->id)->update([
                            'quantity' => $remaining,
                            'updated_at' => $now,
                        ]);
                    } else {
                        DB::table('branch_stocks')->insert([
                            'company_id' => $company->id,
                            'branch_id' => $branch->id,
                            'product_id' => $product->id,
                            'quantity' => $remaining,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('stock_movements')->insert([
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'product_id' => $product->id,
                        'sale_id' => $saleId,
                        'created_by' => $user->id,
                        'movement_type' => 'sale',
                        'quantity_delta' => -$item['quantity'],
                        'balance_after' => $remaining,
                        'note' => $invoiceNumber,
                        'created_at' => $now,
                    ]);
                }
            }

            if ($paidTotal > 0) {
                $remainingChange = $change;
                foreach ($payments as $payment) {
                    $amount = $payment['amount'];
                    if ($payment['method'] === 'cash' && $remainingChange > 0) {
                        $cashChange = min($amount, $remainingChange);
                        $amount = round($amount - $cashChange, 2);
                        $remainingChange = round($remainingChange - $cashChange, 2);
                    }
                    if ($amount <= 0) {
                        continue;
                    }

                    DB::table('sale_payments')->insert([
                        'company_id' => $company->id,
                        'sale_id' => $saleId,
                        'received_by' => $user->id,
                        'method' => $payment['method'],
                        'amount' => $amount,
                        'reference' => $payment['reference'],
                        'paid_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return [
                'sale' => DB::table('sales')->where('company_id', $company->id)->where('id', $saleId)->first(),
                'items' => DB::table('sale_items')->where('company_id', $company->id)->where('sale_id', $saleId)->get(),
                'payments' => DB::table('sale_payments')->where('company_id', $company->id)->where('sale_id', $saleId)->get(),
                'change' => $change,
            ];
        }, attempts: 3);
    }

    public function collectInvoicePayment(User $user, Company $company, int $saleId, array $data): object
    {
        return DB::transaction(function () use ($user, $company, $saleId, $data): object {
            $sale = DB::table('sales')
                ->where('company_id', $company->id)
                ->where('id', $saleId)
                ->lockForUpdate()
                ->first();

            abort_if(! $sale, 404);
            abort_if($sale->payment_status === 'paid', 422, 'This invoice is already paid.');

            $amount = round((float) $data['amount'], 2);
            $dueTotal = (float) $sale->due_total;
            abort_if($amount > $dueTotal, 422, 'Payment cannot exceed this invoice due.');

            $now = now();
            DB::table('sale_payments')->insert([
                'company_id' => $company->id,
                'sale_id' => $saleId,
                'received_by' => $user->id,
                'method' => $data['method'],
                'amount' => $amount,
                'reference' => $data['reference'] ?? null,
                'paid_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $paidTotal = round((float) $sale->paid_total + $amount, 2);
            $dueTotal = round((float) $sale->grand_total - $paidTotal, 2);
            DB::table('sales')
                ->where('company_id', $company->id)
                ->where('id', $saleId)
                ->update([
                    'paid_total' => $paidTotal,
                    'due_total' => $dueTotal,
                    'payment_status' => $this->paymentStatus($paidTotal, $dueTotal),
                    'updated_at' => $now,
                ]);

            return DB::table('sales')->where('company_id', $company->id)->where('id', $saleId)->first();
        }, attempts: 3);
    }

    private function nextInvoiceNumber(Company $company): string
    {
        $prefix = 'MP-'.$company->id.'-'.now()->format('Ymd').'-';
        $lastNumber = DB::table('sales')
            ->where('company_id', $company->id)
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');
        $sequence = $lastNumber ? ((int) substr($lastNumber, -5)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function paymentStatus(float $paid, float $due): string
    {
        if ($due <= 0) {
            return 'paid';
        }

        return $paid > 0 ? 'partial' : 'unpaid';
    }
}
