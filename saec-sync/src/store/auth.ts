import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'
import { invoke } from '@tauri-apps/api/core'

interface StoredCredentials {
  access_token: string
  refresh_token: string
  expires_at: number
  tenant_id: string | null
  user_email: string
}

interface AuthState {
  isAuthenticated: boolean
  user: StoredCredentials | null
  accessToken: string | null
  checkAuth: () => Promise<void>
  login: (accessToken: string, refreshToken: string, expiresIn: number, tenantId: string | null, userEmail: string) => Promise<void>
  logout: () => Promise<void>
  refreshToken: () => Promise<string | null>
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      isAuthenticated: false,
      user: null,
      accessToken: null,

      checkAuth: async () => {
        try {
          const creds = await invoke<StoredCredentials | null>('refresh_credentials')
          console.log('[auth] checkAuth result:', creds ? 'has credentials' : 'no credentials')

          if (!creds) {
            set({ isAuthenticated: false, user: null, accessToken: null })
            return
          }

          const now = Date.now() / 1000

          // Token still valid
          if (creds.expires_at > now + 60) { // 60s buffer
            set({
              isAuthenticated: true,
              user: creds,
              accessToken: creds.access_token,
            })
            return
          }

          // If somehow we still have expired creds
          set({ isAuthenticated: false, user: null, accessToken: null })
        } catch (error) {
          console.error('Auth check failed:', error)
          set({ isAuthenticated: false, user: null, accessToken: null })
        }
      },

      login: async (accessToken, refreshToken, expiresIn, tenantId, userEmail) => {
        const creds: StoredCredentials = {
          access_token: accessToken,
          refresh_token: refreshToken,
          expires_at: Math.floor(Date.now() / 1000) + expiresIn,
          tenant_id: tenantId,
          user_email: userEmail,
        }

        set({
          isAuthenticated: true,
          user: creds,
          accessToken,
        })
      },

      logout: async () => {
        await invoke('auth_logout')
        set({ isAuthenticated: false, user: null, accessToken: null })
      },

      refreshToken: async () => {
        const { user } = get()
        if (!user) return null

        try {
          const creds = await invoke<StoredCredentials | null>('get_credentials')
          if (creds && creds.expires_at > Date.now() / 1000) {
            set({ user: creds, accessToken: creds.access_token })
            return creds.access_token
          }
          return null
        } catch (error) {
          console.error('Token refresh failed:', error)
          return null
        }
      },
    }),
    {
      name: 'saec-sync-auth',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        isAuthenticated: state.isAuthenticated,
        user: state.user,
        accessToken: state.accessToken,
      }),
    }
  )
)