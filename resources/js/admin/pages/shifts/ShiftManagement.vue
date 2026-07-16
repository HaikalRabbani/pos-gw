<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Shift & Jadwal</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola shift, jadwal karyawan, dan laporan rekonsiliasi kas</p>
    </div>

    <!-- Tabs -->
    <Tabs v-model:value="activeTab">
      <TabList>
        <Tab value="0"><i class="pi pi-cog mr-1.5"></i>Master Shift</Tab>
        <Tab value="1"><i class="pi pi-calendar mr-1.5"></i>Penjadwalan</Tab>
        <Tab value="2"><i class="pi pi-chart-bar mr-1.5"></i>Laporan</Tab>
      </TabList>

      <TabPanels>
        <!-- ════════════════════════ TAB 1: MASTER SHIFT ════════════════════════ -->
        <TabPanel value="0">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <p class="text-sm text-slate-600">Atur jenis shift, jam kerja, dan urutan</p>
              <Button v-if="perm.can('manageShifts')" label="Tambah Shift" icon="pi pi-plus" size="small"
                @click="openShiftTypeDialog()" />
            </div>

            <div v-if="shiftTypesLoading" class="animate-pulse space-y-2">
              <div class="h-10 bg-slate-200 rounded-lg"></div>
              <div class="h-10 bg-slate-100 rounded-lg"></div>
            </div>

            <DataTable v-else :value="shiftTypes" stripedRows size="small" class="text-sm">
              <template #empty>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                  <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                    <i class="pi pi-clock text-2xl text-slate-300"></i>
                  </div>
                  <p class="text-slate-500 font-medium">Belum ada jenis shift</p>
                  <p class="text-slate-400 text-xs mt-1">Tambah shift pagi, siang, atau malam</p>
                </div>
              </template>
              <Column header="No." style="width: 50px">
                <template #body="{ index }">
                  <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
                </template>
              </Column>
              <Column field="name" header="Nama Shift" sortable>
                <template #body="{ data }">
                  <span class="font-medium text-slate-900">{{ data.name }}</span>
                </template>
              </Column>
              <Column field="start_time" header="Jam Mulai" sortable style="width: 120px">
                <template #body="{ data }">
                  <span class="font-mono text-sm">{{ data.start_time }}</span>
                </template>
              </Column>
              <Column field="end_time" header="Jam Selesai" sortable style="width: 120px">
                <template #body="{ data }">
                  <span class="font-mono text-sm">{{ data.end_time }}</span>
                </template>
              </Column>
              <Column header="Durasi" style="width: 100px">
                <template #body="{ data }">
                  <span class="text-xs text-slate-500">{{ computeDuration(data.start_time, data.end_time) }}</span>
                </template>
              </Column>
              <Column header="Urutan" style="width: 80px" sortable>
                <template #body="{ data }">
                  <span class="text-xs font-mono text-slate-500">#{{ data.sort_order }}</span>
                </template>
              </Column>
              <Column header="Status" style="width: 100px">
                <template #body="{ data }">
                  <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'"
                    :severity="data.is_active ? 'success' : 'danger'" rounded />
                </template>
              </Column>
              <Column header="Aksi" style="width: 100px">
                <template #body="{ data }">
                  <div class="flex gap-2">
                    <Button icon="pi pi-pencil" text rounded size="small" v-tooltip.top="'Edit'"
                      @click="openShiftTypeDialog(data)" />
                    <Button :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check-circle'"
                      text rounded size="small"
                      :class="data.is_active ? 'text-amber-600' : 'text-teal-600'"
                      v-tooltip.top="data.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                      @click="toggleShiftTypeActive(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" size="small"
                      v-tooltip.top="'Hapus'" @click="confirmDeleteShiftType(data)" />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </TabPanel>

        <!-- ════════════════════════ TAB 2: PENJADWALAN (Calendar) ════════════════════════ -->
        <TabPanel value="1">
          <div class="space-y-4">
            <!-- Month Navigation -->
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <Button icon="pi pi-chevron-left" text rounded severity="secondary" size="small"
                  @click="changeMonth(-1)" />
                <span class="text-lg font-bold text-slate-800 min-w-[170px] text-center tabular-nums">
                  {{ monthNames[calMonth - 1] }} {{ calYear }}
                </span>
                <Button icon="pi pi-chevron-right" text rounded severity="secondary" size="small"
                  @click="changeMonth(1)" />
                <Button icon="pi pi-refresh" text rounded severity="secondary" size="small"
                  v-tooltip.top="'Hari ini'" @click="goToToday" />
              </div>
              <div class="flex items-center gap-2">
                <Button label="Generate" icon="pi pi-magic" severity="contrast" size="small"
                  v-tooltip.top="'Generate jadwal otomatis'" @click="generateDialog = true" />
                <Button label="Tambah" icon="pi pi-plus" size="small"
                  @click="openScheduleDialog()" />
              </div>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-3 text-xs text-slate-500">
              <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-100"></span>Shift</span>
              <span class="text-slate-300">|</span>
              <span>Klik tanggal untuk melihat detail</span>
            </div>

            <!-- Calendar Grid -->
            <div v-if="schedulesLoading" class="animate-pulse">
              <div class="grid grid-cols-7 gap-px bg-slate-200 rounded-xl overflow-hidden">
                <div v-for="i in 7" :key="'h'+i" class="bg-slate-100 h-8"></div>
                <div v-for="i in 35" :key="'c'+i" class="bg-white h-24"></div>
              </div>
            </div>
            <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
              <!-- Day headers -->
              <div class="grid grid-cols-7 divide-x divide-slate-100">
                <div v-for="day in dayHeaders" :key="day"
                  class="px-2 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center bg-slate-50/80">
                  {{ day }}
                </div>
              </div>
              <!-- Day cells -->
              <div class="grid grid-cols-7 divide-x divide-slate-100 border-t border-slate-200">
                <div v-for="(cell, idx) in calendarDays" :key="idx"
                  class="min-h-[105px] border-b border-slate-100 p-1.5 cursor-pointer transition-all duration-150 relative group"
                  :class="[
                    cell.isCurrentMonth ? 'bg-white hover:bg-blue-50/60' : 'bg-slate-50/40',
                    cell.isToday ? 'ring-2 ring-inset ring-blue-400/60' : '',
                  ]"
                  @click="openDayDetail(cell)">
                  <!-- Date number -->
                  <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-semibold rounded-full mb-1"
                    :class="[
                      cell.isToday ? 'bg-blue-500 text-white' : cell.isCurrentMonth ? 'text-slate-700' : 'text-slate-300',
                    ]">
                    {{ cell.day }}
                  </span>
                  <!-- Shift badges -->
                  <div class="space-y-0.5">
                    <div v-for="shift in cell.shifts" :key="shift.shift_type_id"
                      class="text-[10px] leading-tight px-1 py-0.5 rounded truncate font-medium"
                      :class="shiftColors[(shift.shift_type_id || 0) % shiftColors.length]">
                      {{ shift.shift_name }}: {{ shift.user_name }}
                    </div>
                    <div v-if="cell.shifts.length === 0 && cell.isCurrentMonth"
                      class="text-[9px] text-slate-300 italic mt-0.5">—</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- ════════════════════════ TAB 3: LAPORAN SHIFT ════════════════════════ -->
        <TabPanel value="2">
          <div class="space-y-4">
            <!-- Active Shift Info -->
            <div v-if="activeShift"
              class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-800 via-emerald-900 to-emerald-950 p-5">
              <div class="absolute top-0 right-0 w-48 h-48 bg-emerald-400/10 rounded-full -translate-y-1/2 translate-x-1/4 blur-3xl"></div>
              <div class="relative z-10 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-700/50 flex items-center justify-center shrink-0 mt-0.5">
                  <i class="pi pi-user text-emerald-200 text-lg"></i>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-300">Shift Aktif</span>
                  </div>
                  <p class="text-lg font-bold text-white">{{ activeShift.user?.name }}</p>
                  <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm text-emerald-200/80">
                    <span><i class="pi pi-clock mr-1"></i>Mulai: {{ formatDateTime(activeShift.start_at) }}</span>
                    <span><i class="pi pi-money-bill mr-1"></i>Kas Awal: {{ formatRupiah(activeShift.cash_begin) }}</span>
                  </div>
                </div>
                <Tag value="Aktif" severity="success" rounded class="shrink-0" />
              </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center gap-2 mb-1">
                  <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center">
                    <i class="pi pi-calendar text-blue-600 text-sm"></i>
                  </div>
                  <span class="text-[10px] font-semibold uppercase tracking-wider text-blue-600">Total Shift</span>
                </div>
                <p class="text-xl font-bold text-slate-900">{{ totalShifts }}</p>
              </div>
              <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center gap-2 mb-1">
                  <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <i class="pi pi-check-circle text-emerald-600 text-sm"></i>
                  </div>
                  <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600">Selesai</span>
                </div>
                <p class="text-xl font-bold text-slate-900">{{ completedShifts }}</p>
              </div>
              <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center gap-2 mb-1">
                  <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center">
                    <i class="pi pi-exclamation-triangle text-amber-600 text-sm"></i>
                  </div>
                  <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-600">Selisih Kas</span>
                </div>
                <p class="text-xl font-bold text-slate-900">{{ formatRupiah(totalCashDiff) }}</p>
              </div>
            </div>

            <!-- Shift History -->
            <div v-if="reportLoading" class="animate-pulse space-y-2">
              <div class="h-10 bg-slate-200 rounded-lg"></div>
              <div class="h-10 bg-slate-100 rounded-lg"></div>
            </div>

            <DataTable v-else :value="shifts" paginator :rows="10" stripedRows size="small" class="text-sm"
              paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
              currentPageReportTemplate="Halaman {currentPage} dari {totalPages}">
              <template #empty>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                  <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                    <i class="pi pi-history text-2xl text-slate-300"></i>
                  </div>
                  <p class="text-slate-500 font-medium">Belum ada riwayat shift</p>
                  <p class="text-slate-400 text-xs mt-1">Riwayat akan muncul setelah kasir memulai shift</p>
                </div>
              </template>
              <Column header="No." style="width: 50px">
                <template #body="{ index }">
                  <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
                </template>
              </Column>
              <Column field="start_at" header="Tanggal" sortable>
                <template #body="{ data }">
                  <span class="text-slate-900 font-medium">{{ formatDate(data.start_at) }}</span>
                </template>
              </Column>
              <Column field="user.name" header="Kasir" sortable />
              <Column field="start_at" header="Mulai">
                <template #body="{ data }">{{ formatTime(data.start_at) }}</template>
              </Column>
              <Column field="end_at" header="Selesai">
                <template #body="{ data }">{{ data.end_at ? formatTime(data.end_at) : '-' }}</template>
              </Column>
              <Column field="cash_begin" header="Kas Awal">
                <template #body="{ data }"><span class="font-medium">{{ formatRupiah(data.cash_begin) }}</span></template>
              </Column>
              <Column field="cash_expected" header="Kas Harapan">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.cash_expected !== null ? formatRupiah(data.cash_expected) : '-' }}</span>
                </template>
              </Column>
              <Column field="cash_actual" header="Kas Aktual">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.cash_actual !== null ? formatRupiah(data.cash_actual) : '-' }}</span>
                </template>
              </Column>
              <Column field="cash_diff" header="Selisih">
                <template #body="{ data }">
                  <span v-if="data.cash_diff !== null" class="font-semibold"
                    :class="data.cash_diff === 0 ? 'text-slate-700' : data.cash_diff > 0 ? 'text-emerald-600' : 'text-red-600'">
                    {{ data.cash_diff > 0 ? '+' : '' }}{{ formatRupiah(data.cash_diff) }}
                  </span>
                  <span v-else class="text-slate-300">—</span>
                </template>
              </Column>
              <Column field="note" header="Catatan" />
              <Column field="end_at" header="Status">
                <template #body="{ data }">
                  <Tag :value="data.end_at ? 'Selesai' : 'Aktif'"
                    :severity="data.end_at ? 'success' : 'info'" rounded />
                </template>
              </Column>
            </DataTable>
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>

    <!-- ═══ Shift Type Dialog ═══ -->
    <Dialog v-model:visible="shiftTypeDialog" :header="editingShiftType ? 'Edit Shift' : 'Tambah Shift'" modal class="w-md">
      <form @submit.prevent="saveShiftType" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama Shift <span class="text-red-400">*</span></label>
          <InputText v-model="stForm.name" class="w-full" placeholder="Contoh: Pagi" required />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Jam Mulai <span class="text-red-400">*</span></label>
            <InputText v-model="stForm.start_time" type="time" class="w-full" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Jam Selesai <span class="text-red-400">*</span></label>
            <InputText v-model="stForm.end_time" type="time" class="w-full" required />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Urutan</label>
          <InputNumber v-model="stForm.sort_order" class="w-24" :min="0" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="shiftTypeDialog = false" />
          <Button type="submit" :label="editingShiftType ? 'Simpan' : 'Tambah'" :loading="stSaving" />
        </div>
      </form>
    </Dialog>

    <!-- ═══ Schedule Dialog ═══ -->
    <Dialog v-model:visible="scheduleDialog" :header="'Tambah Jadwal' + (scheduleFormDate ? ' — ' + scheduleFormDate : '')" modal class="w-md">
      <form @submit.prevent="saveSchedule" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Karyawan <span class="text-red-400">*</span></label>
          <Select v-model="scForm.user_id" :options="users" optionLabel="name" optionValue="id"
            placeholder="Pilih karyawan" class="w-full" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Shift <span class="text-red-400">*</span></label>
          <Select v-model="scForm.shift_type_id" :options="shiftTypes" optionLabel="name" optionValue="id"
            placeholder="Pilih shift" class="w-full" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal <span class="text-red-400">*</span></label>
          <InputText v-model="scForm.date" type="date" class="w-full" required />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="scheduleDialog = false" />
          <Button type="submit" label="Tambah" :loading="scSaving" />
        </div>
      </form>
    </Dialog>

    <!-- ═══ Generate Dialog ═══ -->
    <Dialog v-model:visible="generateDialog" header="Generate Jadwal Otomatis" modal class="w-md">
      <div class="space-y-4">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-violet-50 border border-violet-100">
          <i class="pi pi-magic text-violet-500 text-xl"></i>
          <p class="text-sm text-violet-700">
            Generate jadwal shift untuk <strong>{{ monthNames[genMonth - 1] }} {{ genYear }}</strong>.
            Karyawan akan diacak merata ke setiap shift setiap hari.
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Pilih Karyawan</label>
          <div class="space-y-1.5 max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-2">
            <div v-for="user in users" :key="user.id"
              class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-slate-50 cursor-pointer">
              <Checkbox :inputId="'gen-user-' + user.id" v-model="genUserIds" :value="user.id" binary />
              <label :for="'gen-user-' + user.id" class="text-sm text-slate-700 cursor-pointer">{{ user.name }}</label>
            </div>
            <div v-if="users.length === 0" class="text-xs text-slate-400 text-center py-3">Tidak ada karyawan</div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Pilih Shift</label>
          <div class="space-y-1.5 border border-slate-200 rounded-xl p-2">
            <div v-for="st in shiftTypes" :key="st.id"
              class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-slate-50 cursor-pointer">
              <Checkbox :inputId="'gen-st-' + st.id" v-model="genShiftTypeIds" :value="st.id" binary />
              <label :for="'gen-st-' + st.id" class="text-sm text-slate-700 cursor-pointer">
                {{ st.name }} ({{ st.start_time }}–{{ st.end_time }})
              </label>
            </div>
            <div v-if="shiftTypes.length === 0" class="text-xs text-slate-400 text-center py-3">
              Buat master shift dulu di tab sebelumnya
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <Button label="Batal" severity="secondary" @click="generateDialog = false" />
          <Button label="Generate Sekarang" icon="pi pi-magic" severity="contrast"
            :loading="genLoading" :disabled="genUserIds.length === 0 || genShiftTypeIds.length === 0"
            @click="processGenerate" />
        </div>
      </div>
    </Dialog>

    <!-- ═══ Day Detail Dialog ═══ -->
    <Dialog v-model:visible="dayDialog" :header="'Jadwal — ' + dayDialogDate" modal class="w-md">
      <div class="space-y-3">
        <div v-if="daySchedules.length === 0" class="text-center py-8 text-slate-400">
          <i class="pi pi-calendar text-3xl text-slate-200 mb-2 block"></i>
          <p class="text-sm">Tidak ada jadwal di tanggal ini</p>
        </div>
        <div v-for="s in daySchedules" :key="s.id"
          class="flex items-center gap-3 p-3 rounded-xl border border-slate-200">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold shrink-0"
            :class="shiftColors[(s.shift_type_id || 0) % shiftColors.length]">
            {{ s.shift_type?.name?.[0] || '?' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-800">{{ s.user?.name }}</p>
            <p class="text-xs text-slate-400">
              {{ s.shift_type?.name }} ({{ s.shift_type?.start_time }}–{{ s.shift_type?.end_time }})
            </p>
          </div>
          <Tag :value="statusLabel(s.status)" :severity="s.status === 'confirmed' ? 'success' : s.status === 'absent' ? 'danger' : 'info'" rounded />
          <Button icon="pi pi-trash" text rounded severity="danger" size="small"
            v-tooltip.top="'Hapus'" @click="deleteSchedule(s)" />
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <Button label="Tambah Jadwal" icon="pi pi-plus" size="small"
            @click="dayDialog = false; openScheduleDialog(dayDialogDate)" />
        </div>
      </div>
    </Dialog>

    <!-- ═══ Delete Confirmation ═══ -->
    <Dialog v-model:visible="deleteDialog" header="Konfirmasi Hapus" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
          <p class="text-sm text-red-700">
            Yakin ingin menghapus <strong>{{ deletingItem?.name || 'jadwal ini' }}</strong>?
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="deleteDialog = false" />
          <Button label="Hapus" severity="danger" :loading="deleting" @click="executeDelete" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { formatRupiah } from '../../utils/format'
import { usePermission } from '../../utils/usePermission'
import { useToastStore } from '../../stores/toast'
import client from '../../api/client'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'

const perm = usePermission()
const toast = useToastStore()

// ────── Tab ──────
const activeTab = ref('0')

// ────── Shared ──────
const outlets = ref([])
const selectedOutletId = ref(null)
const users = ref([])

// ────── Tab 1: Master Shift Types ──────
const shiftTypes = ref([])
const shiftTypesLoading = ref(false)
const shiftTypeDialog = ref(false)
const editingShiftType = ref(false)
const stSaving = ref(false)
const stForm = ref({ name: '', start_time: '07:00', end_time: '15:00', sort_order: 0 })

// ────── Tab 2: Scheduling ──────
const schedules = ref([])
const schedulesLoading = ref(false)
const scheduleDialog = ref(false)
const scSaving = ref(false)
const scForm = ref({ user_id: null, shift_type_id: null, date: '' })
const scheduleFormDate = ref('')

// Calendar
const calYear = ref(new Date().getFullYear())
const calMonth = ref(new Date().getMonth() + 1)
const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const dayHeaders = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
const shiftColors = ['bg-blue-100 text-blue-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700', 'bg-violet-100 text-violet-700', 'bg-rose-100 text-rose-700']

// Generate
const generateDialog = ref(false)
const genLoading = ref(false)
const genUserIds = ref([])
const genShiftTypeIds = ref([])

// Day Detail
const dayDialog = ref(false)
const dayDialogDate = ref('')
const daySchedules = ref([])

const scheduleStatusOptions = [
  { label: 'Terjadwal', value: 'scheduled' },
  { label: 'Dikonfirmasi', value: 'confirmed' },
  { label: 'Absen', value: 'absent' },
]

// Calendar days computed
const calendarDays = computed(() => {
  const firstDay = new Date(calYear.value, calMonth.value - 1, 1)
  const startDayOfWeek = firstDay.getDay() // 0=Sun
  const daysInMonth = new Date(calYear.value, calMonth.value, 0).getDate()
  const daysInPrevMonth = new Date(calYear.value, calMonth.value - 1, 0).getDate()
  const today = new Date()
  const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  // Build schedule lookup: { '2026-07-15': [{ shift_type_id, shift_name, user_name }, ...] }
  const scheduleByDate = {}
  for (const s of schedules.value) {
    if (!scheduleByDate[s.date]) scheduleByDate[s.date] = []
    scheduleByDate[s.date].push({
      shift_type_id: s.shift_type_id,
      shift_name: s.shift_type?.name || 'Shift',
      user_name: s.user?.name || '—',
      schedule_id: s.id,
    })
  }

  const cells = []
  const totalCells = Math.ceil((startDayOfWeek + daysInMonth) / 7) * 7

  for (let i = 0; i < totalCells; i++) {
    let day, isCurrentMonth, dateStr
    if (i < startDayOfWeek) {
      // Previous month
      day = daysInPrevMonth - startDayOfWeek + i + 1
      isCurrentMonth = false
      const m = calMonth.value === 1 ? 12 : calMonth.value - 1
      const y = calMonth.value === 1 ? calYear.value - 1 : calYear.value
      dateStr = `${y}-${String(m).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    } else if (i >= startDayOfWeek + daysInMonth) {
      // Next month
      day = i - startDayOfWeek - daysInMonth + 1
      isCurrentMonth = false
      const m = calMonth.value === 12 ? 1 : calMonth.value + 1
      const y = calMonth.value === 12 ? calYear.value + 1 : calYear.value
      dateStr = `${y}-${String(m).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    } else {
      // Current month
      day = i - startDayOfWeek + 1
      isCurrentMonth = true
      dateStr = `${calYear.value}-${String(calMonth.value).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    }

    cells.push({
      day,
      isCurrentMonth,
      isToday: dateStr === todayStr,
      dateStr,
      shifts: scheduleByDate[dateStr] || [],
    })
  }

  return cells
})

// ────── Tab 3: Shift Reports ──────
const shifts = ref([])
const activeShift = ref(null)
const reportLoading = ref(false)
const totalShifts = ref(0)
const completedShifts = ref(0)
const totalCashDiff = ref(0)

// ────── Delete ──────
const deleteDialog = ref(false)
const deletingItem = ref(null)
const deletingType = ref('')
const deleting = ref(false)

// ────── Helpers ──────
function computeDuration(start, end) {
  if (!start || !end) return '-'
  const [sh, sm] = start.split(':').map(Number)
  const [eh, em] = end.split(':').map(Number)
  const diff = (eh * 60 + em) - (sh * 60 + sm)
  const h = Math.floor(diff / 60)
  const m = diff % 60
  return `${h}j ${m}m`
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatTime(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return formatDate(dateStr) + ' ' + formatTime(dateStr)
}

// ────── Shared Fetch ──────
async function fetchUsers() {
  try {
    const { data } = await client.get('/users')
    users.value = data.data || []
  } catch (_) {}
}

async function fetchOutlets() {
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data
    if (data.data.length > 0 && !selectedOutletId.value) {
      selectedOutletId.value = data.data[0].id
    }
  } catch (_) {}
}

// ────── Tab 1: Shift Types CRUD ──────
async function fetchShiftTypes() {
  if (!selectedOutletId.value) return
  shiftTypesLoading.value = true
  try {
    const { data } = await client.get('/shift-types', { params: { outlet_id: selectedOutletId.value } })
    shiftTypes.value = data.data
  } catch (_) {}
  finally { shiftTypesLoading.value = false }
}

function openShiftTypeDialog(item = null) {
  editingShiftType.value = !!item
  stForm.value = item
    ? { name: item.name, start_time: item.start_time, end_time: item.end_time, sort_order: item.sort_order ?? 0, id: item.id }
    : { name: '', start_time: '07:00', end_time: '15:00', sort_order: 0 }
  shiftTypeDialog.value = true
}

async function saveShiftType() {
  stSaving.value = true
  try {
    const payload = {
      outlet_id: selectedOutletId.value,
      name: stForm.value.name,
      start_time: stForm.value.start_time,
      end_time: stForm.value.end_time,
      sort_order: stForm.value.sort_order ?? 0,
    }
    if (editingShiftType.value) {
      await client.put(`/shift-types/${stForm.value.id}`, payload)
    } else {
      await client.post('/shift-types', payload)
    }
    shiftTypeDialog.value = false
    fetchShiftTypes()
  } catch (e) {
    toast.error('Gagal Simpan Shift', e.response?.data?.message || 'Gagal menyimpan shift')
  } finally { stSaving.value = false }
}

async function toggleShiftTypeActive(item) {
  try {
    await client.put(`/shift-types/${item.id}`, { is_active: !item.is_active })
    fetchShiftTypes()
  } catch (_) {}
}

function confirmDeleteShiftType(item) {
  deletingItem.value = item
  deletingType.value = 'shiftType'
  deleteDialog.value = true
}

// ────── Tab 2: Calendar Schedules ──────
async function fetchSchedules() {
  if (!selectedOutletId.value) return
  schedulesLoading.value = true
  try {
    // Fetch entire month
    const startDate = `${calYear.value}-${String(calMonth.value).padStart(2, '0')}-01`
    const lastDay = new Date(calYear.value, calMonth.value, 0).getDate()
    const endDate = `${calYear.value}-${String(calMonth.value).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`

    const { data } = await client.get('/shift-schedules', {
      params: { outlet_id: selectedOutletId.value, start_date: startDate, end_date: endDate }
    })
    schedules.value = data.data
  } catch (_) {}
  finally { schedulesLoading.value = false }
}

function changeMonth(delta) {
  calMonth.value += delta
  if (calMonth.value < 1) { calMonth.value = 12; calYear.value-- }
  if (calMonth.value > 12) { calMonth.value = 1; calYear.value++ }
  fetchSchedules()
}

function goToToday() {
  const now = new Date()
  calYear.value = now.getFullYear()
  calMonth.value = now.getMonth() + 1
  fetchSchedules()
}

function openScheduleDialog(date = null) {
  const d = date || `${calYear.value}-${String(calMonth.value).padStart(2, '0')}-01`
  scheduleFormDate.value = d
  scForm.value = { user_id: null, shift_type_id: null, date: d }
  scheduleDialog.value = true
}

async function saveSchedule() {
  scSaving.value = true
  try {
    await client.post('/shift-schedules', {
      outlet_id: selectedOutletId.value,
      user_id: scForm.value.user_id,
      shift_type_id: scForm.value.shift_type_id,
      date: scForm.value.date,
    })
    scheduleDialog.value = false
    fetchSchedules()
  } catch (e) {
    toast.error('Gagal Tambah Jadwal', e.response?.data?.message || 'Gagal menambah jadwal')
  } finally { scSaving.value = false }
}

function deleteSchedule(item) {
  deletingItem.value = { ...item, name: `Jadwal ${item.user?.name} - ${item.date}` }
  deletingType.value = 'schedule'
  deleteDialog.value = true
}

function statusLabel(s) {
  const map = { scheduled: 'Terjadwal', confirmed: 'Dikonfirmasi', absent: 'Absen' }
  return map[s] || s
}

// ────── Day Detail ──────
function openDayDetail(cell) {
  dayDialogDate.value = cell.dateStr
  daySchedules.value = cell.shifts.map(s => {
    // Find full schedule data
    const full = schedules.value.find(sch => sch.id === s.schedule_id)
    return full || s
  })
  dayDialog.value = true
}

// ────── Generate ──────
async function processGenerate() {
  genLoading.value = true
  try {
    const { data } = await client.post('/shift-schedules/generate', {
      outlet_id: selectedOutletId.value,
      year: calYear.value,
      month: calMonth.value,
      user_ids: genUserIds.value,
      shift_type_ids: genShiftTypeIds.value,
    })
    toast.success('Generate Berhasil', data.message || 'Jadwal berhasil digenerate')
    generateDialog.value = false
    fetchSchedules()
  } catch (e) {
    toast.error('Gagal Generate', e.response?.data?.message || 'Gagal generate jadwal')
  } finally { genLoading.value = false }
}

// ────── Tab 3: Shift Reports ──────
async function fetchShiftReports() {
  if (!selectedOutletId.value) return
  reportLoading.value = true
  try {
    const { data } = await client.get('/shifts', { params: { outlet_id: selectedOutletId.value, per_page: 50 } })
    shifts.value = data.data || []
    activeShift.value = shifts.value.find(s => !s.end_at) || null
    totalShifts.value = data.meta?.total || 0
    const completed = shifts.value.filter(s => s.end_at)
    completedShifts.value = completed.length
    totalCashDiff.value = completed.reduce((sum, s) => sum + (s.cash_diff || 0), 0)
  } catch (_) {}
  finally { reportLoading.value = false }
}

// ────── Delete ──────
async function executeDelete() {
  deleting.value = true
  try {
    if (deletingType.value === 'shiftType') {
      await client.delete(`/shift-types/${deletingItem.value.id}`)
      fetchShiftTypes()
    } else if (deletingType.value === 'schedule') {
      await client.delete(`/shift-schedules/${deletingItem.value.id}`)
      fetchSchedules()
    }
    deleteDialog.value = false
  } catch (e) {
    toast.error('Gagal Hapus', 'Gagal menghapus data')
  } finally { deleting.value = false }
}

// ────── Init ──────
onMounted(async () => {
  await fetchOutlets()
  if (selectedOutletId.value) {
    fetchShiftTypes()
    fetchSchedules()
    fetchShiftReports()
  }
  fetchUsers()
})
</script>
