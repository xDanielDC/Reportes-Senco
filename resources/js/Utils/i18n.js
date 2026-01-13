import { createI18n } from 'vue-i18n'
const messages = {
    es: {
        validations: {
            required: "Campo obligatorio",
            requiredIf: "Campo obligatorio",
            minLength: "Debes escribir mínimo {min} caracteres",
            maxLength: "Debes escribir máximo {max} caracteres",
            minValue: "El valor mínimo permitido es {min}",
            maxValue: "El valor máximo permitido es {max}",
            numeric: "Solo se permiten números",
            email: "Correo electrónico no válido",
            sameAs: "{property} y {otherName} deben coincidir",
            alphaNum: "El valor debe ser alfa numérico",
            undefined: "NA"
        }
    }
}

export const i18n = createI18n({
    locale: 'es',
    fallbackLocale: 'en',
    messages
})

