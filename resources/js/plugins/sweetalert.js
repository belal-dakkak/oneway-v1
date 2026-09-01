import Swal from 'sweetalert2'

const BrandedSwal = Swal.mixin({
    confirmButtonColor: '#c20000',
    cancelButtonColor: '#d33',
    reverseButtons: true,
    customClass: {
        confirmButton: 'rounded-lg font-bold px-6 py-2',
        cancelButton: 'rounded-lg font-bold px-6 py-2',
        popup: 'rounded-2xl shadow-2xl border border-border'
    }
})

export const toast = (options) => {
    const isRTL = document.documentElement.dir === 'rtl'
    return BrandedSwal.fire({
        toast: true,
        position: isRTL ? 'top-start' : 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        ...options
    })
}

export const alert = (options) => {
    return BrandedSwal.fire(options)
}

export const confirm = (options) => {
    return BrandedSwal.fire({
        showCancelButton: true,
        ...options
    })
}

export default BrandedSwal
