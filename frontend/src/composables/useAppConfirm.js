import { useConfirm } from 'primevue/useconfirm'

export const useAppConfirm = () => {
  const confirm = useConfirm()

  return ({ header = 'Please confirm', message, acceptLabel = 'Continue', accept }) => {
    confirm.require({
      group: 'app',
      header,
      message,
      acceptLabel,
      rejectLabel: 'Cancel',
      accept,
    })
  }
}
