import * as validators from '@vuelidate/validators'
import {createI18nMessage, helpers as help} from '@vuelidate/validators'
import {i18n} from "./i18n"

const withI18nMessage = createI18nMessage({t: i18n.global.t.bind(i18n)})

export const helpers = help
export const required = withI18nMessage(validators.required)
export const minLength = withI18nMessage(validators.minLength, {withArguments: true})
export const maxLength = withI18nMessage(validators.maxLength, {withArguments: true})
export const minValue = withI18nMessage(validators.minValue, {withArguments: true})
export const maxValue = withI18nMessage(validators.maxValue, {withArguments: true})
export const numeric = withI18nMessage(validators.numeric)
export const requiredIf = withI18nMessage(validators.requiredIf, {withArguments: true})
export const email = withI18nMessage(validators.email)
export const sameAs = withI18nMessage(validators.sameAs, {withArguments: true})
export const alphaNum = withI18nMessage(validators.alphaNum)
export const alpha = withI18nMessage(validators.alpha)
