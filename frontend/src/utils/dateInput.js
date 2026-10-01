export const parseDateInput = (value) => {
  if (!value) return null
  if (value instanceof Date) return value

  const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (!match) return null

  return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
}

export const formatDateInput = (value) => {
  if (!value) return null
  if (typeof value === 'string') return value.slice(0, 10) || null
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) return null

  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}
