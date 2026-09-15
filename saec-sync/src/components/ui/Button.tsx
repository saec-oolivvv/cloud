import { forwardRef, ButtonHTMLAttributes } from 'react'
import { cn } from '../../utils/format'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = 'primary', size = 'md', loading, disabled, children, ...props }, ref) => {
    const baseStyles = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed'

    const variants = {
      primary: 'bg-saec-600 text-white hover:bg-saec-700 active:bg-saec-800 focus-visible:ring-saec-500 focus-visible:ring-offset-white dark:focus-visible:ring-offset-saec-950 shadow-sm',
      secondary: 'bg-saec-100 text-saec-900 hover:bg-saec-200 active:bg-saec-300 dark:bg-saec-800 dark:text-saec-100 dark:hover:bg-saec-700 dark:active:bg-saec-600 focus-visible:ring-saec-500 focus-visible:ring-offset-white dark:focus-visible:ring-offset-saec-950',
      ghost: 'bg-transparent hover:bg-saec-100 dark:hover:bg-saec-800 active:bg-saec-200 dark:active:bg-saec-700 focus-visible:ring-saec-500 focus-visible:ring-offset-white dark:focus-visible:ring-offset-saec-950',
      danger: 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus-visible:ring-red-500 focus-visible:ring-offset-white dark:focus-visible:ring-offset-saec-950 shadow-sm',
      outline: 'border border-saec-300 dark:border-saec-600 bg-transparent hover:bg-saec-100 dark:hover:bg-saec-800 focus-visible:ring-saec-500 focus-visible:ring-offset-white dark:focus-visible:ring-offset-saec-950',
    }

    const sizes = {
      sm: 'px-3 py-1.5 text-sm gap-1.5',
      md: 'px-4 py-2 text-sm gap-2',
      lg: 'px-6 py-3 text-base gap-2',
    }

    return (
      <button
        ref={ref}
        className={cn(baseStyles, variants[variant], sizes[size], className)}
        disabled={disabled || loading}
        {...props}
      >
        {loading && <svg className="w-4 h-4 animate-spin" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>}
        {children}
      </button>
    )
  }
)

Button.displayName = 'Button'