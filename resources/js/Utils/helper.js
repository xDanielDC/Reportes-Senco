import dayjs from "dayjs";
import duration from "dayjs/plugin/duration";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";

import 'dayjs/locale/es'

dayjs.extend(duration);
dayjs.extend(utc);
dayjs.extend(timezone);

dayjs.tz.setDefault('America/Bogota')
dayjs.locale('es')

const helpers = {
    formatCurrency(number, decimals = 0) {
        const number_value = Number(parseFloat(number).toFixed(2))

        if (typeof number_value === "undefined") {
            return number
        }
        const formatter = new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: decimals
        });
        return formatter.format(number_value);
    },

    formatDate(date, format) {
        return dayjs(date).tz("America/Bogota").format(format ?? 'DD-MM-YYYY hh:mm A')
    },

    Loading(title = null, text = null) {
        Swal.fire({
            showConfirmButton: false,
            showCancelButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            title: title ? title : 'Procesando solicitud…',
            text: text ? text : 'Este proceso puede tardar unos segundos, espere por favor.',
        });
    },

    loadIcon() {
        let el = document.getElementById('swalHTML');
        return el.innerHTML;
    },
}


const install = (app) => {
    app.config.globalProperties.$h = helpers;
};

export {install as default, helpers as helper};
