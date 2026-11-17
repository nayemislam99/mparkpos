<?php

namespace App\Services\Pos;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TenantContext
{
    public function company(User $user, mixed $requestedCompanyId = null): Company
    {
        $companyId = $user->type === 'super_admin'
            ? ($requestedCompanyId ?: $user->company_id)
            : $user->company_id;

        if (
            ! $companyId
            || (
                $user->type !== 'super_admin'
                && $requestedCompanyId !== null
                && (int) $requestedCompanyId !== (int) $user->company_id
            )
        ) {
            throw ValidationException::withMessages([
                'company_id' => 'You cannot access this store.',
            ]);
        }

        return Company::query()
            ->whereKey($companyId)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function branch(Company $company, User $user, mixed $requestedBranchId = null): Branch
    {
        $query = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true);

        if ($requestedBranchId) {
            return $query->whereKey($requestedBranchId)->firstOrFail();
        }

        $userBranch = $user->branch_id
            ? (clone $query)->whereKey($user->branch_id)->first()
            : null;

        if ($userBranch) {
            return $userBranch;
        }

        return $query->where('is_head_office', true)->firstOrFail();
    }
}
