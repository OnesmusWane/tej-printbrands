import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

import Dashboard       from './pages/Dashboard.vue'
import Services        from './pages/Services.vue'
import Pricing         from './pages/Pricing.vue'
import Portfolio       from './pages/Portfolio.vue'
import Gallery         from './pages/Gallery.vue'
import Testimonials    from './pages/Testimonials.vue'
import Brands          from './pages/Brands.vue'
import Faqs            from './pages/Faqs.vue'
import Products        from './pages/Products.vue'
import PageContent     from './pages/PageContent.vue'
import ServiceRequests from './pages/ServiceRequests.vue'
import Bookings        from './pages/Bookings.vue'
import Quotations      from './pages/Quotations.vue'
import Invoices        from './pages/Invoices.vue'
import Payments        from './pages/Payments.vue'
import Tasks           from './pages/Tasks.vue'
import Settings        from './pages/Settings.vue'
import Users           from './pages/Users.vue'
import Blog            from './pages/Blog.vue'
import Projects        from './pages/Projects.vue'
import Orders         from './pages/Orders.vue'

const routes = [
    { path: '/',                   component: Dashboard,       meta: { title: 'Dashboard',        perm: 'dashboard.view' } },
    { path: '/services',           component: Services,        meta: { title: 'Services',          perm: 'content.view' } },
    { path: '/pricing',            component: Pricing,         meta: { title: 'Pricing',           perm: 'content.view' } },
    { path: '/portfolio',          component: Portfolio,       meta: { title: 'Portfolio',         perm: 'content.view' } },
    { path: '/gallery',            component: Gallery,         meta: { title: 'Gallery',           perm: 'content.view' } },
    { path: '/testimonials',       component: Testimonials,    meta: { title: 'Testimonials',      perm: 'content.view' } },
    { path: '/brands',             component: Brands,          meta: { title: 'Brands',            perm: 'content.view' } },
    { path: '/faqs',               component: Faqs,            meta: { title: 'FAQs',              perm: 'content.view' } },
    { path: '/products',           component: Products,        meta: { title: 'Products',          perm: 'products.view' } },
    { path: '/page-content',       component: PageContent,     meta: { title: 'Page Content',      perm: 'content.view' } },
    { path: '/service-requests',   component: ServiceRequests, meta: { title: 'Service Requests',  perm: 'service_requests.view' } },
    { path: '/bookings',           component: Bookings,        meta: { title: 'Bookings',          perm: 'bookings.view' } },
    { path: '/quotations',         component: Quotations,      meta: { title: 'Quotations',        perm: 'quotations.view' } },
    { path: '/invoices',           component: Invoices,        meta: { title: 'Invoices',          perm: 'invoices.view' } },
    { path: '/payments',           component: Payments,        meta: { title: 'Payments',          perm: 'payments.view' } },
    { path: '/orders',             component: Orders,          meta: { title: 'Orders',            perm: 'orders.view' } },
    { path: '/tasks',              component: Tasks,           meta: { title: 'Tasks',             perm: 'tasks.view' } },
    { path: '/blog',              component: Blog,            meta: { title: 'Blog',              perm: 'content.view' } },
    { path: '/projects',          component: Projects,        meta: { title: 'Projects',          perm: 'tasks.view' } },
    { path: '/settings',           component: Settings,        meta: { title: 'Settings',          perm: 'settings.view' } },
    { path: '/users',              component: Users,           meta: { title: 'Users',             perm: 'users.view' } },
]

export const router = createRouter({
    history: createWebHistory('/admin'),
    routes,
})

router.afterEach(to => {
    document.title = `${to.meta.title as string} | Admin`
})

router.beforeEach((to) => {
    const perm = to.meta.perm as string | undefined
    if (!perm || to.path === '/') return true  // dashboard always accessible
    const auth = useAuthStore()
    if (!auth.can(perm)) return '/'
    return true
})
