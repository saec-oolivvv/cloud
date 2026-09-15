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
          const creds = await invoke<StoredCredentials | null>('get_credentials')
          if (creds && creds.expires_at > Date.now() / 1000) {
            set({
              isAuthenticated: true,
              user: creds,
              accessToken: creds.access_token,
            })
          } else if (creds) {
            // Token expired, try to refresh
            const newToken = await get().refreshToken()
            if (newToken) {
              set({ accessToken: newToken })
            } else {
              await get().logout()
            }
          }
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

        await invoke('store_credentials', {
          access_token: accessToken,
          refresh_token: refreshToken,
          expires_in: expiresIn,
          tenant_id: tenantId,
          user_email: userEmail,
        })

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
          // The backend handles token refresh automatically via the API client
          // This is just a placeholder - actual refresh happens in the Rust layer
          const creds = await invoke<StoredCredentials | null>('get_credentials')
          if (creds) {
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