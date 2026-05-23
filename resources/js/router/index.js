import { createRouter, createWebHistory } from 'vue-router';
import Home from '../views/Home.vue';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import PatientDashboard from '../views/patient/Dashboard.vue';
import StaffDashboard from '../views/staff/Dashboard.vue';
import StaffPatients from '../views/staff/Patients.vue';
import StaffPatientDetail from '../views/staff/PatientDetail.vue';
import StaffPatientForm from '../views/staff/PatientForm.vue';
import StaffMessages from '../views/staff/Messages.vue';
import StaffUnavailability from '../views/staff/Unavailability.vue';
import StaffAppointments from '../views/staff/Appointments.vue';
import StaffReports from '../views/staff/Reports.vue';


const routes = [

    { path: '/', name: 'Home', component: Home, meta: { guest: true } },
    { path: '/login', name: 'Login', component: Login, meta: { guest: true } },
    { path: '/register', name: 'Register', component: Register, meta: { guest: true } },
    { path: '/patient/dashboard', name: 'PatientDashboard', component: PatientDashboard, meta: { requiresAuth: true, role: 'patient' } },
    { path: '/staff/dashboard', name: 'StaffDashboard', component: StaffDashboard, meta: { requiresAuth: true, role: 'staff' } },
    { path: '/staff/patients', name: 'StaffPatients', component: StaffPatients, meta: { requiresAuth: true, role: 'staff' } },
    { path: '/staff/patients/new', name: 'StaffPatientNew', component: StaffPatientForm, meta: { requiresAuth: true, role: 'staff' } },
    { path: '/staff/patients/:id', name: 'StaffPatientDetail', component: StaffPatientDetail, meta: { requiresAuth: true, role: 'staff' } },

    { path: '/staff/messages', name: 'StaffMessages', component: StaffMessages, meta: { requiresAuth: true, role: 'staff' } },
    { path: '/staff/unavailability', name: 'StaffUnavailability', component: StaffUnavailability, meta: { requiresAuth: true, role: 'staff' } },
    { path: '/staff/appointments', name: 'StaffAppointments', component: StaffAppointments, meta: { requiresAuth: true, role: 'staff' } },
    { path: '/staff/reports', name: 'StaffReports', component: StaffReports, meta: { requiresAuth: true, role: 'staff' } },

];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    // Token está em cookie HttpOnly (inacessível via JS).
    // Verificamos se o usuário está logado pela presença do 'user' no localStorage.
    const user = JSON.parse(localStorage.getItem('user') || 'null');

    if (to.meta.requiresAuth && !user) {
        return next({ name: 'Login' });
    }

    if (to.meta.guest && user) {
        if (user?.role === 0) return next({ name: 'PatientDashboard' });
        return next({ name: 'StaffDashboard' });
    }

    if (to.meta.role === 'patient' && user?.role !== 0) {
        return next({ name: 'StaffDashboard' });
    }

    if (to.meta.role === 'staff' && user?.role === 0) {
        return next({ name: 'PatientDashboard' });
    }

    next();
});


export default router;
