import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useClientPortalAuthStore } from '@/features/client-portal/store/clientPortalAuth'

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(to, _from, savedPosition) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    if (savedPosition) return savedPosition
    return { top: 0 }
  },
  routes: [
    {
      path: '/',
      component: () => import('@/layouts/PublicLayout.vue'),
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('@/features/landing/pages/HomeView.vue'),
        },
        {
          path: 'about',
          name: 'about',
          component: () => import('@/features/landing/pages/AboutView.vue'),
        },
        {
          path: 'contact',
          name: 'contact',
          component: () => import('@/features/landing/pages/ContactView.vue'),
        },
        {
          path: 'request',
          name: 'request',
          component: () => import('@/features/landing/pages/RequestView.vue'),
        },
      ],
    },
    {
      path: '/login',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        { path: '', name: 'login', component: () => import('@/features/auth/pages/LoginView.vue') },
      ],
    },
    {
      path: '/forgot-password',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        {
          path: '',
          name: 'forgot-password',
          component: () => import('@/features/auth/pages/ForgotPasswordView.vue'),
        },
      ],
    },
    {
      path: '/reset-password',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        {
          path: '',
          name: 'reset-password',
          component: () => import('@/features/auth/pages/ResetPasswordView.vue'),
        },
      ],
    },
    {
      path: '/dashboard',
      component: () => import('@/layouts/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/features/dashboard/pages/DashboardHomeView.vue'),
        },
        {
          path: 'leads',
          name: 'dashboard-leads',
          component: () => import('@/features/leads/pages/LeadsView.vue'),
        },
        {
          path: 'leads/:id',
          name: 'dashboard-lead-detail',
          component: () => import('@/features/leads/pages/LeadDetailView.vue'),
        },
        {
          path: 'clients',
          name: 'dashboard-clients',
          component: () => import('@/features/clients/pages/ClientsView.vue'),
        },
        {
          path: 'clients/:id',
          name: 'dashboard-client-detail',
          component: () => import('@/features/clients/pages/ClientDetailView.vue'),
        },
        {
          path: 'projects',
          name: 'dashboard-projects',
          component: () => import('@/features/projects/pages/ProjectsView.vue'),
        },
        {
          path: 'projects/:id',
          name: 'dashboard-project-detail',
          component: () => import('@/features/projects/pages/ProjectDetailView.vue'),
        },
        {
          path: 'documents',
          name: 'dashboard-documents',
          component: () => import('@/features/documents/pages/DocumentsView.vue'),
        },
        {
          path: 'documents/proposals/:id',
          name: 'dashboard-document-proposal',
          component: () => import('@/features/documents/pages/ProposalDetailView.vue'),
        },
        {
          path: 'documents/contracts/:id',
          name: 'dashboard-document-contract',
          component: () => import('@/features/documents/pages/ContractDetailView.vue'),
        },
        {
          path: 'documents/invoices/:id',
          name: 'dashboard-document-invoice',
          component: () => import('@/features/documents/pages/InvoiceDetailView.vue'),
        },
        {
          path: 'analytics',
          name: 'dashboard-analytics',
          component: () => import('@/features/analytics/pages/AnalyticsView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'meetings',
          name: 'dashboard-meetings',
          component: () => import('@/features/meetings/pages/MeetingsView.vue'),
        },
        {
          path: 'meetings/:id',
          name: 'dashboard-meeting-detail',
          component: () => import('@/features/meetings/pages/MeetingDetailView.vue'),
        },
      ],
    },
    {
      path: '/settings',
      component: () => import('@/layouts/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          component: () => import('@/features/settings/pages/SettingsView.vue'),
          children: [
            {
              path: '',
              redirect: () => (useAuthStore().isAdmin ? '/settings/company' : '/settings/notifications'),
            },
            {
              path: 'company',
              name: 'settings-company',
              meta: { requiresAdmin: true },
              component: () => import('@/features/settings/pages/CompanySettingsView.vue'),
            },
            {
              path: 'team',
              name: 'settings-team',
              meta: { requiresAdmin: true },
              component: () => import('@/features/settings/pages/TeamSettingsView.vue'),
            },
            {
              path: 'notifications',
              name: 'settings-notifications',
              component: () => import('@/features/settings/pages/NotificationsSettingsView.vue'),
            },
            {
              path: 'security',
              name: 'settings-security',
              component: () => import('@/features/settings/pages/SecuritySettingsView.vue'),
            },
          ],
        },
      ],
    },
    {
      path: '/client/login',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        {
          path: '',
          name: 'client-login',
          component: () => import('@/features/client-portal/pages/ClientLoginView.vue'),
        },
      ],
    },
    {
      path: '/client/reset-password',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        {
          path: '',
          name: 'client-reset-password',
          component: () => import('@/features/auth/pages/ResetPasswordView.vue'),
        },
      ],
    },
    {
      path: '/client',
      component: () => import('@/layouts/ClientLayout.vue'),
      meta: { requiresClientAuth: true },
      children: [
        {
          path: '',
          name: 'client-dashboard',
          component: () => import('@/features/client-portal/pages/ClientDashboardView.vue'),
        },
        {
          path: 'projects',
          name: 'client-projects',
          component: () => import('@/features/client-portal/pages/ClientProjectsView.vue'),
        },
        {
          path: 'projects/new',
          name: 'client-new-project-request',
          component: () => import('@/features/client-portal/pages/ClientNewProjectRequestView.vue'),
        },
        {
          path: 'projects/:id',
          name: 'client-project-detail',
          component: () => import('@/features/client-portal/pages/ClientProjectDetailView.vue'),
        },
        {
          path: 'documents',
          name: 'client-documents',
          component: () => import('@/features/client-portal/pages/ClientDocumentsView.vue'),
        },
        {
          path: 'meetings',
          name: 'client-meetings',
          component: () => import('@/features/client-portal/pages/ClientMeetingsView.vue'),
        },
        {
          path: 'meetings/:id',
          name: 'client-meeting-detail',
          component: () => import('@/features/client-portal/pages/ClientMeetingDetailView.vue'),
        },
        {
          path: 'timeline',
          name: 'client-timeline',
          component: () => import('@/features/client-portal/pages/ClientTimelineView.vue'),
        },
        {
          path: 'profile',
          name: 'client-profile',
          component: () => import('@/features/client-portal/pages/ClientProfileView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  const clientAuth = useClientPortalAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && auth.isAuthenticated) {
    return { path: '/dashboard' }
  }

  // Team Members can't see analytics (revenue data) or Company/Team settings — Admins only.
  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { path: to.path.startsWith('/settings') ? '/settings/notifications' : '/dashboard' }
  }

  if (to.meta.requiresClientAuth && !clientAuth.isAuthenticated) {
    return { path: '/client/login', query: { redirect: to.fullPath } }
  }

  // A client who hasn't changed their emailed temporary password yet is
  // confined to the profile page (where the change-password form lives)
  // until they do.
  if (
    to.meta.requiresClientAuth &&
    clientAuth.user?.mustChangePassword &&
    to.name !== 'client-profile'
  ) {
    return { path: '/client/profile' }
  }

  if (to.name === 'client-login' && clientAuth.isAuthenticated) {
    return { path: '/client' }
  }
})

export default router
