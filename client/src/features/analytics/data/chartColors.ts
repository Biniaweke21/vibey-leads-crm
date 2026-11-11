/**
 * Categorical hues, fixed order (never cycled/reassigned per-render).
 * Green and red are excluded here — reserved as status colors (see below)
 * so a chart series never impersonates a status meaning elsewhere in the app.
 */
export const categoricalPalette = [
  '#2a78d6', // blue
  '#1baf7a', // aqua
  '#eda100', // yellow
  '#4a3aa7', // violet
  '#e87ba4', // magenta
  '#eb6834', // orange
  '#898781', // neutral (for "Other")
]

/** Reserved status meanings — never reused for plain categorical series. */
export const statusColors = {
  good: '#0ca30c',
  warning: '#fab219',
  critical: '#d03b3b',
  neutral: '#898781',
}

/** Single-hue sequential ramp for trend lines and magnitude bars. */
export const sequentialHue = '#2a78d6'

export const chartChrome = {
  gridline: '#e1e0d9',
  axis: '#c3c2b7',
  mutedText: '#898781',
}
