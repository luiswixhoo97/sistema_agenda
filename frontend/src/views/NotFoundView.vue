<template>
  <div class="not-found">
    <div class="not-found-content">
      <div class="error-icon">🔍</div>
      <h1>404</h1>
      <h2>Página no encontrada</h2>
      <p>Lo sentimos, la página que buscas no existe o ha sido movida.</p>
      
      <div class="actions">
        <router-link to="/" class="btn-primary">
          Ir al inicio
        </router-link>
        <button class="btn-secondary" @click="$router.back()">
          Volver atrás
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.not-found {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
  padding: 20px;
}

.not-found-content {
  text-align: center;
  max-width: 400px;
}

.error-icon {
  font-size: 80px;
  margin-bottom: 20px;
}

h1 {
  font-size: 96px;
  font-weight: 700;
  color: #d81b60;
  margin: 0;
  line-height: 1;
}

h2 {
  font-size: 24px;
  color: #333;
  margin: 16px 0 12px;
}

p {
  color: #666;
  margin: 0 0 32px;
}

.actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-primary {
  padding: 14px 32px;
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}

.btn-secondary {
  padding: 14px 32px;
  background: transparent;
  color: #d81b60;
  border: 2px solid #d81b60;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
</style>

