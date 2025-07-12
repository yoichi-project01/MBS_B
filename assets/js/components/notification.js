
/* ==================================
   Notification Component (SweetAlert)
   ================================== */

export function showErrorMessage(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'エラー',
            text: message,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'OK',
            customClass: {
                container: 'swal-z-index'
            }
        });
    } else {
        alert('エラー: ' + message);
    }
}

export function showSuccessMessage(title, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: title,
            html: message,
            confirmButtonColor: '#2f5d3f',
            confirmButtonText: 'OK',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            },
            customClass: {
                container: 'swal-z-index'
            }
        });
    } else {
        alert(title + ': ' + message);
    }
}

export function showInfoMessage(title, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: title,
            text: message,
            confirmButtonColor: '#2f5d3f',
            confirmButtonText: 'OK',
            customClass: {
                container: 'swal-z-index'
            }
        });
    } else {
        alert(title + ': ' + message);
    }
}
