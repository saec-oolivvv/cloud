import { useUIStore, Notification } from '@/store'
import { X, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-react'
import { useEffect, useState } from 'react'

export function Toaster() {
  const { notifications, removeNotification } = useUIStore()
  const [mounted, setMounted] = useState(false)

  useEffect(() => {
    setMounted(true)
  }, [])

  if (!mounted || notifications.length === 0) return null

  const icons = {
    success: <CheckCircle className="w-5 h-5 text-green-500" />,
    error: <AlertCircle className="w-5 h-5 text-red-500" />,
    warning: <AlertTriangle className="w-5 h-5 text-yellow-500" />,
    info: <Info className="w-5 h-5 text-blue-500" />,
  }

  const bgColors = {
    success: 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800',
    error: 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800',
    warning: 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800',
    info: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800',
  }

  return (
    <div
      className="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
      role="region"
      aria-label="Notifications"
      aria-live="polite"
    >
      {notifications.map((notification) => (
        <Toast
          key={notification.id}
          notification={notification}
          icon={icons[notification.type]}
          bgColor={bgColors[notification.type]}
          onClose={() => removeNotification(notification.id)}
        />
      ))}
    </div>
  )
}

interface ToastProps {
  notification: Notification
  icon: React.ReactNode
  bgColor: string
  onClose: () => void
}

function Toast({ notification, icon, bgColor, onClose }: ToastProps) {
  const [visible, setVisible] = useState(true)

  const handleClose = () => {
    setVisible(false)
    setTimeout(onClose, 200)
  }

  return (
    <div
      className={`pointer-events-auto transform transition-all duration-300 ease-out ${
        visible ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'
      }`}
      role="alert"
    >
      <div className={`${bgColor} border rounded-xl shadow-lg min-w-[300px] max-w-[450px] overflow-hidden`}>
        <div className="flex items-start gap-3 p-4">
          <div className="flex-shrink-0 mt-0.5">{icon}</div>
          <div className="flex-1 min-w-0">
            <p className="font-medium text-saec-900 dark:text-saec-100">{notification.title}</p>
            {notification.message && (
              <p className="text-sm text-saec-600 dark:text-saec-400 mt-1">{notification.message}</p>
            )}
          </div>
          <button
            onClick={handleClose}
            className="flex-shrink-0 p-1 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 transition-colors text-saec-400 hover:text-saec-600"
            aria-label="Fermer"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
        <div className="h-1 bg-black/5 dark:bg-white/5" />
      </div>
    </div>
  )
}