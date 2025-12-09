<template>
  <div class="home-view">
    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-content">
        <div class="hero-logo">💅</div>
        <h1>BeautySpa</h1>
        <p class="hero-subtitle">Tu espacio de belleza y bienestar</p>
        
        <div class="hero-actions">
          <router-link to="/" class="btn-primary">
            Agendar Cita
          </router-link>
          <router-link to="/login" class="btn-secondary">
            Acceso Personal
          </router-link>
        </div>
      </div>

      <div class="hero-decoration">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
      </div>
    </section>

    <!-- Servicios Section -->
    <section class="servicios-section">
      <h2>Nuestros Servicios</h2>
      
      <div class="servicios-grid">
        <div class="servicio-card">
          <div class="servicio-icon">💇‍♀️</div>
          <h3>Corte y Peinado</h3>
          <p>Cortes modernos y peinados para toda ocasión</p>
        </div>

        <div class="servicio-card">
          <div class="servicio-icon">🎨</div>
          <h3>Coloración</h3>
          <p>Tintes, mechas, balayage y más</p>
        </div>

        <div class="servicio-card">
          <div class="servicio-icon">💅</div>
          <h3>Manicure</h3>
          <p>Uñas acrílicas, gel y tradicional</p>
        </div>

        <div class="servicio-card">
          <div class="servicio-icon">🧖‍♀️</div>
          <h3>Faciales</h3>
          <p>Tratamientos de limpieza y rejuvenecimiento</p>
        </div>

        <div class="servicio-card">
          <div class="servicio-icon">💆‍♀️</div>
          <h3>Masajes</h3>
          <p>Relajantes, descontracturantes y más</p>
        </div>

        <div class="servicio-card">
          <div class="servicio-icon">👰</div>
          <h3>Eventos</h3>
          <p>Paquetes para novias y quinceañeras</p>
        </div>
      </div>
    </section>

    <!-- Por qué elegirnos -->
    <section class="beneficios-section">
      <h2>¿Por qué elegirnos?</h2>
      
      <div class="beneficios-grid">
        <div class="beneficio-item">
          <div class="beneficio-icon">⭐</div>
          <h4>Profesionales Certificados</h4>
          <p>Equipo con años de experiencia</p>
        </div>

        <div class="beneficio-item">
          <div class="beneficio-icon">📱</div>
          <h4>Agenda Online</h4>
          <p>Reserva desde tu celular 24/7</p>
        </div>

        <div class="beneficio-item">
          <div class="beneficio-icon">💝</div>
          <h4>Promociones Exclusivas</h4>
          <p>Ofertas especiales para clientes</p>
        </div>

        <div class="beneficio-item">
          <div class="beneficio-icon">🔔</div>
          <h4>Recordatorios</h4>
          <p>Te avisamos antes de tu cita</p>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="cta-content">
        <h2>¿Lista para lucir increíble?</h2>
        <p>Agenda tu cita ahora y recibe un 10% de descuento en tu primera visita</p>
          <router-link to="/" class="btn-cta">
            Agendar mi cita
        </router-link>
      </div>
    </section>

    <!-- Footer -->
    <footer class="home-footer">
      <div class="footer-content">
        <div class="footer-brand">
          <span class="footer-logo">💅</span>
          <span class="footer-name">BeautySpa</span>
        </div>
        
        <div class="footer-info">
          <p>📍 Calle Principal #123, Ciudad</p>
          <p>📞 (555) 123-4567</p>
          <p>📧 contacto@beautyspa.com</p>
        </div>

        <div class="footer-hours">
          <h4>Horario</h4>
          <p>Lunes a Viernes: 9:00 - 20:00</p>
          <p>Sábados: 9:00 - 18:00</p>
          <p>Domingos: Cerrado</p>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>© 2025 BeautySpa. Todos los derechos reservados.</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// Redirigir si el usuario ya está logueado
onMounted(() => {
  if (authStore.isAuthenticated) {
    const tipo = authStore.userType;
    if (tipo === 'admin') {
      router.push('/admin');
    } else if (tipo === 'empleado') {
      router.push('/empleado/calendario');
    } else if (tipo === 'cliente') {
      router.push('/agendar');
    }
  }
});
</script>

<style scoped>
.home-view {
  min-height: 100vh;
  background: white;
}

/* Hero */
.hero {
  min-height: 80vh;
  background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 50%, #f48fb1 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  position: relative;
  overflow: hidden;
}

.hero-content {
  text-align: center;
  position: relative;
  z-index: 1;
}

.hero-logo {
  font-size: 80px;
  margin-bottom: 16px;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.hero h1 {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 56px;
  color: #880e4f;
  margin: 0 0 8px;
  font-weight: 700;
}

.hero-subtitle {
  font-size: 20px;
  color: #ad1457;
  margin: 0 0 40px;
}

.hero-actions {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 280px;
  margin: 0 auto;
}

.btn-primary {
  padding: 16px 32px;
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  color: white;
  border: none;
  border-radius: 30px;
  font-size: 18px;
  font-weight: 600;
  text-decoration: none;
  box-shadow: 0 4px 20px rgba(216, 27, 96, 0.4);
  transition: transform 0.3s, box-shadow 0.3s;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 30px rgba(216, 27, 96, 0.5);
}

.btn-secondary {
  padding: 14px 32px;
  background: transparent;
  color: #880e4f;
  border: 2px solid #880e4f;
  border-radius: 30px;
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.3s, color 0.3s;
}

.btn-secondary:hover {
  background: #880e4f;
  color: white;
}

.hero-decoration {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.blob {
  position: absolute;
  border-radius: 50%;
  opacity: 0.3;
}

.blob-1 {
  width: 300px;
  height: 300px;
  background: #d81b60;
  top: -100px;
  right: -100px;
}

.blob-2 {
  width: 200px;
  height: 200px;
  background: #880e4f;
  bottom: -50px;
  left: -50px;
}

/* Servicios */
.servicios-section {
  padding: 80px 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.servicios-section h2 {
  text-align: center;
  font-size: 36px;
  color: #333;
  margin-bottom: 48px;
}

.servicios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.servicio-card {
  background: white;
  border-radius: 20px;
  padding: 32px 24px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s, box-shadow 0.3s;
}

.servicio-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.servicio-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.servicio-card h3 {
  font-size: 20px;
  color: #333;
  margin: 0 0 8px;
}

.servicio-card p {
  font-size: 14px;
  color: #666;
  margin: 0;
}

/* Beneficios */
.beneficios-section {
  padding: 80px 20px;
  background: #fce4ec;
}

.beneficios-section h2 {
  text-align: center;
  font-size: 36px;
  color: #333;
  margin-bottom: 48px;
}

.beneficios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 32px;
  max-width: 1000px;
  margin: 0 auto;
}

.beneficio-item {
  text-align: center;
}

.beneficio-icon {
  font-size: 40px;
  margin-bottom: 12px;
}

.beneficio-item h4 {
  font-size: 18px;
  color: #333;
  margin: 0 0 8px;
}

.beneficio-item p {
  font-size: 14px;
  color: #666;
  margin: 0;
}

/* CTA */
.cta-section {
  padding: 80px 20px;
  background: linear-gradient(135deg, #880e4f 0%, #d81b60 100%);
}

.cta-content {
  max-width: 600px;
  margin: 0 auto;
  text-align: center;
  color: white;
}

.cta-content h2 {
  font-size: 36px;
  margin: 0 0 16px;
}

.cta-content p {
  font-size: 18px;
  margin: 0 0 32px;
  opacity: 0.9;
}

.btn-cta {
  display: inline-block;
  padding: 16px 40px;
  background: white;
  color: #d81b60;
  border-radius: 30px;
  font-size: 18px;
  font-weight: 600;
  text-decoration: none;
  transition: transform 0.3s, box-shadow 0.3s;
}

.btn-cta:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 30px rgba(0, 0, 0, 0.3);
}

/* Footer */
.home-footer {
  background: #1a1a2e;
  color: white;
  padding: 60px 20px 20px;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 40px;
  max-width: 1000px;
  margin: 0 auto 40px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.footer-logo {
  font-size: 32px;
}

.footer-name {
  font-family: 'Playfair Display', serif;
  font-size: 24px;
  font-weight: 700;
}

.footer-info p,
.footer-hours p {
  margin: 8px 0;
  font-size: 14px;
  opacity: 0.8;
}

.footer-hours h4 {
  margin: 0 0 12px;
  font-size: 16px;
}

.footer-bottom {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-bottom p {
  margin: 0;
  font-size: 13px;
  opacity: 0.6;
}

/* Responsive */
@media (max-width: 768px) {
  .hero h1 {
    font-size: 40px;
  }
  
  .hero-subtitle {
    font-size: 16px;
  }
  
  .servicios-section h2,
  .beneficios-section h2,
  .cta-content h2 {
    font-size: 28px;
  }
}
</style>
