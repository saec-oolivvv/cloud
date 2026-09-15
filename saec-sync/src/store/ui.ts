import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

interface UIState {
  sidebarOpen: boolean
  setSidebarOpen: (open: boolean) => void
  toggleSidebar: () => void
  activeView: 'dashboard' | 'files' | 'conflicts' | 'settings'
  setActiveView: (view: UIState['activeView']) => void
  theme: 'light' | 'dark' | 'system'
  setTheme: (theme: UIState['theme']) => void
  notifications: Notification[]
  addNotification: (notification: Omit<Notification, 'id' | 'timestamp'>) => void
  removeNotification: (id: string) => void
}

export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  timestamp: number
  duration?: number
}

export const useUIStore = create<UIState>()(
  persist(
    (set, get) => ({
      sidebarOpen: true,
      setSidebarOpen: (open) => set({ sidebarOpen: open }),
      toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),

      activeView: 'dashboard',
      setActiveView: (view) => set({ activeView: view }),

      theme: 'system',
      setTheme: (theme) => {
        set({ theme })
        // Apply theme to document
        const root = document.documentElement
        if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
          root.classList.add('dark')
        } else {
          root.classList.remove('dark')
        }
      },

      notifications: [],
      addNotification: (notification) => {
        const id = crypto.randomUUID()
        const newNotification = {
          ...notification,
          id,
          timestamp: Date.now(),
        }
        set((state) => ({
          notifications: [...state.notifications, newNotification],
        }))

        // Auto-remove after duration
        const duration = notification.duration ?? 5000
        setTimeout(() => {
          get().removeNotification(id)
        }, duration)
      },

      removeNotification: (id) => {
        set((state) => ({
          notifications: state.notifications.filter((n) => n.id !== id),
        }))
      },
    }),
    {
      name: 'saec-sync-ui',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        sidebarOpen: state.sidebarOpen,
        activeView: state.activeView,
        theme: state.theme,
      }),
      onRehydrateStorage: () => (state) => {
        if (state) {
          // Apply theme on rehydration
          const root = document.documentElement
          if (state.theme === 'dark' || (state.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark')
          } else {
            root.classList.remove('dark')
          }
        }
      },
    }
  )
)