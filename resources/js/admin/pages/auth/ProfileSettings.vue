<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Pengaturan Profil</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola data akun Anda</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="animate-pulse space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex items-center gap-4 mb-6">
          <div class="w-20 h-20 rounded-full bg-slate-200"></div>
          <div class="space-y-2 flex-1">
            <div class="h-5 w-32 bg-slate-200 rounded"></div>
            <div class="h-3 w-48 bg-slate-100 rounded"></div>
          </div>
        </div>
        <div class="space-y-3">
          <div class="h-10 bg-slate-100 rounded-lg"></div>
          <div class="h-10 bg-slate-100 rounded-lg"></div>
        </div>
      </div>
    </div>

    <template v-else>
      <!-- Alert -->
      <transition name="fade">
        <div v-if="alert.show"
          class="px-4 py-3 rounded-xl border flex items-center gap-3 text-sm"
          :class="alert.type === 'success'
            ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
            : 'bg-red-50 border-red-200 text-red-800'">
          <CheckCircle v-if="alert.type === 'success'" class="w-5 h-5 text-emerald-500 shrink-0" stroke-width="1.5" />
          <AlertCircle v-else class="w-5 h-5 text-red-500 shrink-0" stroke-width="1.5" />
          <span>{{ alert.message }}</span>
        </div>
      </transition>

      <!-- Profile Info Card -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-100">
          <div class="flex items-center gap-4">
            <div class="relative">
              <img v-if="photoPreview || user.photo"
                :src="photoPreview || user.photo"
                class="w-20 h-20 rounded-full object-cover border-2 border-slate-200" />
              <div v-else
                class="w-20 h-20 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center text-xl font-bold text-white shadow-sm">
                {{ initials }}
              </div>
              <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-400 border-2 border-white"></div>
            </div>
            <div>
              <p class="text-lg font-bold text-slate-900">{{ user.name }}</p>
              <p class="text-sm text-slate-500">{{ user.email }}</p>
              <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-semibold mt-1"
                :class="roleBadgeClass(auth.highestRole)">
                {{ auth.roleLabel }}
              </span>
            </div>
          </div>
        </div>

        <form @submit.prevent="saveProfile" class="p-6 space-y-5">
          <!-- Photo Upload -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Foto Profil</label>
            <div class="flex items-center gap-3">
              <input type="file" accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer"
                @change="onFileSelect" />
              <button v-if="photoPreview || user.photo" type="button"
                class="text-xs text-red-500 hover:text-red-700 transition"
                @click="removePhoto">Hapus</button>
            </div>
            <p v-if="uploadError" class="text-xs text-red-500 mt-1">{{ uploadError }}</p>
          </div>

          <!-- Nama -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
            <InputText v-model="form.name" class="w-full" required />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <InputText v-model="form.email" type="email" class="w-full" required />
          </div>

          <hr class="border-slate-200">

          <!-- Password (kosongkan jika tidak ingin ganti) -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password Baru</label>
            <InputText v-model="form.password" type="password" class="w-full"
              placeholder="Kosongkan jika tidak ingin mengubah" />
            <p class="text-xs text-slate-400 mt-1">Minimal 8 karakter. Biarkan kosong jika tidak ingin mengganti.</p>
          </div>

          <!-- PIN -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">PIN (6 digit)</label>
            <InputMask v-model="form.pin" mask="999999" placeholder="******"
              class="w-full text-center text-xl" />
            <p class="text-xs text-slate-400 mt-1">Kosongkan jika tidak ingin mengubah.</p>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <Button type="submit" label="Simpan Perubahan" :loading="saving">
              <template #icon>
                <Check class="w-4 h-4" stroke-width="1.5" />
              </template>
            </Button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { roleBadgeClass } from '../../utils/roleBadge'
import client from '../../api/client'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Button from 'primevue/button'
import { Check, CheckCircle, AlertCircle } from 'lucide-vue-next'

const auth = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const selectedFile = ref(null)
const photoPreview = ref(null)
const uploadError = ref('')

const alert = ref({ show: false, type: 'success', message: '' })

const user = computed(() => auth.user || {})

const initials = computed(() => {
  const name = user.value.name || ''
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

const form = ref({
  name: '',
  email: '',
  password: '',
  pin: '',
})

const removeCurrentPhoto = ref(false)

onMounted(() => {
  form.value = {
    name: auth.user?.name || '',
    email: auth.user?.email || '',
    password: '',
    pin: '',
  }
})

function showAlert(msg, type = 'success') {
  alert.value = { show: true, type, message: msg }
  setTimeout(() => { alert.value.show = false }, 4000)
}

function onFileSelect(e) {
  const file = e.target.files[0]
  if (!file) return
  if (!file.type.startsWith('image/')) {
    uploadError.value = 'File harus berupa gambar'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    uploadError.value = 'Maksimal ukuran file 2MB'
    return
  }
  selectedFile.value = file
  uploadError.value = ''
  const reader = new FileReader()
  reader.onload = (ev) => { photoPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}

function removePhoto() {
  selectedFile.value = null
  photoPreview.value = null
  removeCurrentPhoto.value = true
}

async function saveProfile() {
  saving.value = true
  try {
    // Upload photo dulu kalo ada file baru
    let photoUrl = auth.user?.photo || null
    if (removeCurrentPhoto.value) {
      photoUrl = null
      removeCurrentPhoto.value = false
    } else if (selectedFile.value) {
      const formData = new FormData()
      formData.append('file', selectedFile.value)
      const { data } = await client.post('/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      photoUrl = data.url
    }

    const payload = {
      name: form.value.name,
      email: form.value.email,
      photo: photoUrl,
    }
    if (form.value.password) payload.password = form.value.password
    if (form.value.pin) payload.pin = form.value.pin

    const { data } = await client.put('/auth/me', payload)

    // Update auth store
    auth.user = data.data
    localStorage.setItem('pos_user', JSON.stringify(data.data))

    showAlert('Profil berhasil diperbarui!', 'success')
  } catch (e) {
    const msg = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'Gagal menyimpan profil'
    showAlert(msg, 'error')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
