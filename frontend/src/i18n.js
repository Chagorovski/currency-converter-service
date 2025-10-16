import { createI18n } from 'vue-i18n'

export const i18n = createI18n({
  legacy: false,
  locale: navigator.language || 'en-US',
  messages: {
    'en-US': {
      title: 'Currency Converter',
      amount: 'Amount',
      from: 'From',
      to: 'To',
      convert: 'Convert',
      result: 'Result',
      error: 'Error'
    }
  }
})
