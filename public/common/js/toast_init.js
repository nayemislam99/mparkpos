
let Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000
});

function showToast(type, message) {
    if (typeof Toast !== 'undefined') {
        Toast.fire({
            icon: type,
            title: message
        });
    }
}

