<template>
  <div class="paso-datos">
    <!-- Header -->
    <div class="paso-header">
      <div class="header-icon">
        <i class="fa fa-user-circle"></i>
      </div>
      <h2>Tus Datos</h2>
      <p>Ingresa tu información para agendar</p>
    </div>

    <!-- Formulario -->
    <div class="form-card">
      <div class="form-row">
        <div class="form-group">
          <label>Nombre <span class="required">*</span></label>
          <input
            type="text"
            v-model="store.datosCliente.nombre"
            placeholder="Juan"
            maxlength="50"
          />
        </div>
        <div class="form-group">
          <label>Apellido <span class="required">*</span></label>
          <input
            type="text"
            v-model="store.datosCliente.apellido"
            placeholder="Pérez"
            maxlength="50"
          />
        </div>
      </div>

      <div class="form-group">
        <label>Teléfono (10 dígitos) <span class="required">*</span></label>
        <div class="input-with-icon">
          <i class="fa fa-phone"></i>
          <input
            type="tel"
            v-model="store.datosCliente.telefono"
            placeholder="5512345678"
            maxlength="10"
            @input="onlyNumbers"
          />
        </div>
        <span class="input-hint">
          <i class="fa fa-whatsapp"></i>
          Te enviaremos un código por WhatsApp para confirmar tu cita
        </span>
      </div>

      <div class="form-group">
        <label>Email <span class="optional">(opcional)</span></label>
        <div class="input-with-icon">
          <i class="fa fa-envelope"></i>
          <input
            type="email"
            v-model="store.datosCliente.email"
            placeholder="tu@email.com"
            maxlength="100"
          />
        </div>
        <span class="input-hint">
          <i class="fa fa-info-circle"></i>
          Para enviarte confirmación y recordatorios
        </span>
      </div>
    </div>

    <!-- Validación -->
    <div class="validation-card" :class="{ 'complete': validacion.completo === validacion.total }">
      <div class="validation-icon">
        <i v-if="validacion.completo === validacion.total" class="fa fa-check-circle"></i>
        <span v-else>{{ validacion.completo }}/{{ validacion.total }}</span>
      </div>
      <div class="validation-text">
        <strong>{{ validacion.completo === validacion.total ? '¡Todo listo!' : 'Completa los campos' }}</strong>
        <span>{{ validacion.completo === validacion.total ? 'Puedes continuar' : 'Los campos con * son obligatorios' }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useCitasStore } from '@/stores/citas'

const store = useCitasStore()

function onlyNumbers(e: Event) {
  const input = e.target as HTMLInputElement
  input.value = input.value.replace(/\D/g, '')
  store.datosCliente.telefono = input.value
}

const validacion = computed(() => {
  const campos = [
    { valido: store.datosCliente.nombre.length >= 2 },
    { valido: store.datosCliente.apellido.length >= 2 },
    { valido: store.datosCliente.telefono.length === 10 },
  ]
  return {
    completo: campos.filter(c => c.valido).length,
    total: campos.length
  }
})
</script>

<style scoped>
.paso-datos {
  padding: 16px;
}

/* Header */
.paso-header {
  text-align: center;
  padding: 24px 0;
}

.header-icon {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.header-icon i {
  font-size: 32px;
  color: #ec407a;
}

.paso-header h2 {
  font-size: 22px;
  font-weight: 700;
  color: #333;
  margin: 0 0 8px;
}

.theme-dark .paso-header h2 {
  color: #fff;
}

.paso-header p {
  font-size: 14px;
  color: #888;
  margin: 0;
}

/* Form Card */
.form-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.theme-dark .form-card {
  background: #1e1e1e;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #555;
  margin-bottom: 8px;
}

.theme-dark .form-group label {
  color: #bbb;
}

.form-group label .required {
  color: #ec407a;
}

.form-group label .optional {
  color: #999;
  font-weight: 400;
  font-size: 12px;
}

.form-group input {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e8e8e8;
  border-radius: 12px;
  font-size: 15px;
  background: #fafafa;
  color: #333;
  transition: all 0.2s;
}

.theme-dark .form-group input {
  background: #2a2a2a;
  border-color: #333;
  color: #fff;
}

.form-group input:focus {
  outline: none;
  border-color: #ec407a;
  background: white;
  box-shadow: 0 0 0 3px rgba(236, 64, 122, 0.1);
}

.theme-dark .form-group input:focus {
  background: #333;
}

.form-group input::placeholder {
  color: #bbb;
}

.theme-dark .form-group input::placeholder {
  color: #666;
}

/* Input with icon */
.input-with-icon {
  position: relative;
}

.input-with-icon i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 14px;
}

.input-with-icon input {
  padding-left: 42px;
}

/* Input hint */
.input-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 12px;
  color: #888;
}

.input-hint i {
  font-size: 12px;
  color: #25d366;
}

/* Validation Card */
.validation-card {
  display: flex;
  align-items: center;
  gap: 14px;
  background: white;
  border-radius: 14px;
  padding: 16px;
  margin-top: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  border-left: 4px solid #ffc107;
}

.theme-dark .validation-card {
  background: #1e1e1e;
}

.validation-card.complete {
  border-left-color: #4caf50;
}

.validation-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #ffc107;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: white;
}

.validation-card.complete .validation-icon {
  background: #4caf50;
}

.validation-icon i {
  font-size: 20px;
}

.validation-text {
  flex: 1;
}

.validation-text strong {
  display: block;
  font-size: 14px;
  color: #333;
}

.theme-dark .validation-text strong {
  color: #fff;
}

.validation-text span {
  font-size: 12px;
  color: #888;
}

@media (max-width: 400px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 0;
  }
}
</style>
