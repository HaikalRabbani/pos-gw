<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Pesanan</h1>
      <p class="text-sm text-slate-500 mt-1">Daftar dan kelola transaksi</p>
    </div>

    <!-- Filter Bar -->
    <div class="flex flex-wrap items-center gap-3">
      <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value"
        placeholder="Semua Status" class="w-44" @change="fetchOrders" />
      <Select v-model="filterPayment" :options="paymentOptions" optionLabel="label" optionValue="value"
        placeholder="Semua Pembayaran" class="w-48" @change="fetchOrders" />
      <Button label="Refresh" severity="secondary" text size="small" @click="fetchOrders">
        <template #icon>
          <RefreshCw class="w-4 h-4" stroke-width="1.5" />
        </template>
      </Button>
      <Button v-if="perm.isAdmin" label="Sample Order" severity="contrast" text size="small"
        v-tooltip.top="'Buat data dummy untuk testing Split/Merge'"
        @click="seedTestOrder" :disabled="seeding" :loading="seeding">
        <template #icon>
          <FlaskConical class="w-4 h-4" stroke-width="1.5" />
        </template>
      </Button>
    </div>

    <!-- ═══ Merge Zone — muncul ketika >= 2 order dipilih ═══ -->
    <transition name="merge-slide">
      <div v-if="perm.can('mergeBill') && selectedOrderIds.length >= 2"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-700 via-indigo-800 to-indigo-950">
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-400/10 rounded-full -translate-y-1/3 translate-x-1/3 blur-3xl"></div>

        <div class="relative z-10 p-4 space-y-3">
          <!-- Header -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-8 h-8 rounded-xl bg-indigo-600/50 flex items-center justify-center">
                <ArrowUpCircle class="w-4 h-4 text-white" stroke-width="1.5" />
              </span>
              <div>
                <h3 class="text-sm font-bold text-white">Merge Bill</h3>
                <p class="text-[10px] text-indigo-200">{{ selectedOrderIds.length }} pesanan dipilih</p>
              </div>
            </div>
            <Button label="Batal Pilih" text size="small" class="text-indigo-200 hover:text-white"
              @click="selectedOrders = []">
              <template #icon>
                <X class="w-4 h-4" stroke-width="1.5" />
              </template>
            </Button>
          </div>

          <!-- Selected Order Cards -->
          <div class="flex flex-wrap gap-2">
            <div v-for="o in selectedOrders" :key="o.id"
              class="flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-3 py-2 border border-white/10 min-w-[180px]">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs font-mono text-indigo-200">#{{ o.id }}</span>
                  <Tag :value="statusLabel(o.status)" :severity="statusSeverity(o.status)" rounded class="text-[9px]" />
                </div>
                <p class="text-xs text-white/80 truncate mt-0.5">{{ o.customer_name || 'Tanpa nama' }}</p>
                <p class="text-[11px] font-bold text-white mt-0.5">{{ formatRupiah(o.grand_total) }}</p>
              </div>
              <Button text rounded size="small" class="text-white/40 hover:text-white shrink-0"
                @click.stop="removeFromMerge(o)">
                <template #icon>
                  <XCircle class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
            </div>
          </div>

          <!-- Merged Preview (items combined) -->
          <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3 border border-white/10">
            <div class="flex items-center justify-between mb-2">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-indigo-200">Preview Gabungan</span>
              <span class="text-sm font-bold text-white">{{ formatRupiah(mergePreviewTotal) }}</span>
            </div>
            <div class="space-y-1">
              <div v-for="(item, i) in mergePreviewItems" :key="i"
                class="flex items-center justify-between text-xs">
                <span class="text-indigo-100 truncate">{{ item.name }}</span>
                <span class="text-white font-medium shrink-0 ml-2">x{{ item.qty }}</span>
              </div>
              <div v-if="mergePreviewItems.length === 0" class="text-xs text-indigo-300/60 italic">
                Memuat item...
              </div>
            </div>
          </div>

          <!-- Customer Name + Action -->
          <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
              <User class="w-3 h-3 absolute left-3 top-1/2 -translate-y-1/2 text-indigo-300" stroke-width="1.5" />
              <InputText v-model="mergeCustomerName" class="w-full pl-8 text-sm bg-white/10 border-white/20 text-white placeholder:text-indigo-300/50"
                placeholder="Nama pelanggan gabungan (opsional)" />
            </div>
            <Button label="Merge Sekarang" severity="contrast" size="small"
              :loading="mergeSaving" @click="processMerge"
              class="bg-white text-indigo-800 hover:bg-indigo-50 border-0">
              <template #icon>
                <ArrowUpCircle class="w-4 h-4" stroke-width="1.5" />
              </template>
            </Button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-2xl border border-slate-200 p-6">
      <div class="animate-pulse space-y-3">
        <div class="h-10 bg-slate-200 rounded-lg w-full"></div>
        <div class="h-10 bg-slate-100 rounded-lg w-full"></div>
        <div class="h-10 bg-slate-100 rounded-lg w-full"></div>
      </div>
    </div>

    <!-- Orders Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
      <DataTable :value="orders" paginator :rows="rowsPerPage" stripedRows size="small"
        paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
        currentPageReportTemplate="Halaman {currentPage} dari {totalPages}"
        sortField="created_at" :sortOrder="-1"
        v-model:selection="selectedOrders"
        dataKey="id"
        class="text-sm">
        <Column selectionMode="multiple" headerStyle="width: 2rem" style="width: 40px">
        </Column>
        <template #empty>
          <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
              <Receipt class="w-6 h-6 text-slate-300" stroke-width="1.5" />
            </div>
            <p class="text-slate-500 font-medium">Belum ada pesanan</p>
            <p class="text-slate-400 text-xs mt-1">Pesanan baru akan muncul di sini</p>
          </div>
        </template>
        <Column header="No." style="width: 50px">
          <template #body="{ index }">
            <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
          </template>
        </Column>
        <Column field="id" header="ID" sortable style="width: 70px" />
        <Column field="customer_name" header="Pelanggan" sortable>
          <template #body="{ data }">
            <span>{{ data.customer_name || '—' }}</span>
          </template>
        </Column>
        <Column field="status" header="Status" sortable style="width: 120px">
          <template #body="{ data }">
            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" rounded />
          </template>
        </Column>
        <Column field="grand_total" header="Total" sortable style="width: 130px">
          <template #body="{ data }">
            <span class="font-semibold text-slate-900">{{ formatRupiah(data.grand_total) }}</span>
          </template>
        </Column>
        <Column field="payment_status" header="Pembayaran" sortable style="width: 120px">
          <template #body="{ data }">
            <Tag :value="paymentLabel(data)" :severity="paymentSeverity(data)" rounded />
          </template>
        </Column>
        <Column field="created_at" header="Tanggal" sortable style="width: 160px">
          <template #body="{ data }">
            <span class="text-slate-500 text-xs">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>
        <Column header="Aksi" style="width: 60px">
          <template #body="{ data }">
            <div @click.stop>
              <Button severity="secondary" text rounded size="small"
                v-tooltip.top="'Detail Pesanan'" @click="openDetail(data)">
                <template #icon>
                  <Eye class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
            </div>
          </template>
        </Column>
      </DataTable>
      </div>
    </div>

    <!-- Detail Dialog — Tabbed (Info, Items, Refund, Split, Log) -->
    <Dialog v-model:visible="dialogVisible" :header="'Pesanan #' + selectedOrder?.id" modal
      class="w-full max-w-3xl" :closable="true" @hide="resetModes">
      <div v-if="selectedOrder" class="max-h-[70vh] overflow-y-auto pr-1">
        <Tabs v-model:value="orderTab">
          <TabList>
            <Tab value="0"><Receipt class="w-4 h-4 mr-1.5" stroke-width="1.5" />Pesanan</Tab>
            <Tab value="1"><Undo2 class="w-4 h-4 mr-1.5" stroke-width="1.5" />Refund</Tab>
            <Tab value="2"><GitBranch class="w-4 h-4 mr-1.5" stroke-width="1.5" />Split</Tab>
            <Tab value="3"><History class="w-4 h-4 mr-1.5" stroke-width="1.5" />Log</Tab>
          </TabList>
          <TabPanels>
            <!-- ══ TAB 0: Info + Items (gabung) ══ -->
            <TabPanel value="0">
              <div class="flex flex-wrap items-start gap-3 mb-4">
                <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[130px]">
                  <p class="text-xs text-slate-400 mb-0.5">Pelanggan</p>
                  <p class="font-medium text-slate-800">{{ selectedOrder.customer_name || '—' }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[100px]">
                  <p class="text-xs text-slate-400 mb-0.5">Meja</p>
                  <p class="font-medium text-slate-800">{{ selectedOrder.table?.name || '—' }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[130px]">
                  <p class="text-xs text-slate-400 mb-0.5">Kasir</p>
                  <p class="font-medium text-slate-800">{{ selectedOrder.user?.name || '—' }}</p>
                </div>
                <div v-if="selectedOrder.bill_group_id" class="bg-violet-50 border border-violet-200 rounded-xl px-3 py-2.5 text-sm min-w-[140px]">
                  <p class="text-xs text-violet-500 mb-0.5">Bill Group</p>
                  <p class="font-mono text-xs text-violet-700 truncate max-w-[160px]" :title="selectedOrder.bill_group_id">
                    {{ selectedOrder.bill_group_id.substring(0, 12) }}...
                  </p>
                </div>
                <template v-for="payment in selectedOrder.payments" :key="payment.id">
                  <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[130px]">
                    <p class="text-xs text-slate-400 capitalize mb-0.5">{{ payment.method }}</p>
                    <p class="font-semibold text-slate-900">{{ formatRupiah(payment.amount) }}</p>
                    <p v-if="payment.refunded_amount > 0" class="text-[10px] text-orange-500 mt-0.5">
                      refund {{ formatRupiah(payment.refunded_amount) }}
                    </p>
                  </div>
                </template>
                <div v-if="!selectedOrder.payments?.length && selectedOrder.payment_status === 'pending'" class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[130px]">
                  <p class="text-xs font-medium text-amber-600">Belum dibayar</p>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex flex-wrap gap-2 mb-4">
                <Button v-if="perm.can('voidOrder') && canVoid(selectedOrder)" label="Void"
                  severity="danger" text size="small" :loading="voidLoading" @click="confirmVoid">
                  <template #icon>
                    <Ban class="w-4 h-4" stroke-width="1.5" />
                  </template>
                </Button>
                <Button v-if="perm.can('refundOrder') && canRefund(selectedOrder)" label="Refund"
                  severity="warning" text size="small" @click="orderTab='1'">
                  <template #icon>
                    <Undo2 class="w-4 h-4" stroke-width="1.5" />
                  </template>
                </Button>
                <Button v-if="perm.can('splitBill') && canSplit(selectedOrder)" label="Split Bill"
                  severity="contrast" text size="small" @click="orderTab='2'">
                  <template #icon>
                    <GitBranch class="w-4 h-4" stroke-width="1.5" />
                  </template>
                </Button>
              </div>

              <!-- Items -->
              <div class="space-y-2">
                <div v-for="item in selectedOrder.items" :key="item.id"
                  class="border border-slate-100 rounded-xl p-3">
                  <p class="text-sm font-medium text-slate-800">{{ item.product_name }}</p>
                  <p v-if="item.notes" class="text-xs text-slate-500 italic mt-0.5">{{ item.notes }}</p>
                  <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500">
                    <span>x{{ item.qty }}</span>
                    <span>@ {{ formatRupiah(item.unit_price) }}</span>
                    <span class="font-medium text-slate-700">= {{ formatRupiah(item.total_price) }}</span>
                    <span v-if="item.refunded_qty > 0" class="ml-auto text-orange-500">Refund {{ item.refunded_qty }}x</span>
                  </div>
                </div>
              </div>
            </TabPanel>

            <!-- ══ TAB 1: Refund ══ -->
            <TabPanel value="1">
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-slate-800">Refund Item</h3>
                  <span class="text-xs text-slate-400">Total dibayar: {{ formatRupiah(totalPaid) }}</span>
                </div>
                <div class="space-y-2">
                  <div v-for="item in refundableItems" :key="item.id"
                    class="flex items-center gap-3 p-3 rounded-xl border border-slate-200"
                    :class="{ 'bg-blue-50 border-blue-200': refundQtys[item.id] > 0 }">
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-slate-800 truncate">{{ item.product_name }}</p>
                      <p class="text-xs text-slate-400">
                        Pesan {{ item.qty }}x @ {{ formatRupiah(item.unit_price) }}
                        <span v-if="item.refunded_qty > 0" class="text-orange-500">
                          — sudah refund {{ item.refunded_qty }}
                        </span>
                      </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                      <button @click="adjustRefund(item.id, -1)"
                        :disabled="(refundQtys[item.id] || 0) <= 0"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-sm font-bold transition-all"
                        :class="(refundQtys[item.id] || 0) > 0
                          ? 'bg-red-100 text-red-600 hover:bg-red-200 active:scale-95'
                          : 'bg-slate-100 text-slate-300 cursor-not-allowed'">-</button>
                      <span class="w-6 text-center font-bold text-base tabular-nums text-slate-800">
                        {{ refundQtys[item.id] || 0 }}
                      </span>
                      <button @click="adjustRefund(item.id, 1)"
                        :disabled="(refundQtys[item.id] || 0) >= item.refundable_qty"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-sm font-bold transition-all"
                        :class="(refundQtys[item.id] || 0) < item.refundable_qty
                          ? 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200 active:scale-95'
                          : 'bg-slate-100 text-slate-300 cursor-not-allowed'">+</button>
                      <span class="text-xs text-slate-400 w-8 text-right">/{{ item.refundable_qty }}</span>
                    </div>
                  </div>
                </div>
                <div v-if="totalRefundAmount > 0" class="bg-teal-50 border border-teal-200 rounded-xl p-3">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-teal-800">Total Refund</span>
                    <span class="text-lg font-bold text-teal-700">{{ formatRupiah(totalRefundAmount) }}</span>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-medium text-teal-700">Alasan Refund</label>
                    <Textarea v-model="refundReason" rows="1" class="w-full text-sm"
                      placeholder="Alasan refund (wajib diisi)" />
                  </div>
                  <div class="flex justify-end gap-2 mt-3">
                    <Button label="Proses Refund" severity="danger" size="small"
                      :loading="refundLoading" @click="processRefund">
                      <template #icon>
                        <Undo2 class="w-4 h-4" stroke-width="1.5" />
                      </template>
                    </Button>
                  </div>
                </div>
              </div>
            </TabPanel>

            <!-- ══ TAB 2: Split ══ -->
            <TabPanel value="2">
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-slate-800">Split Bill</h3>
                  <span class="text-xs text-slate-400">Pisahkan item ke beberapa tagihan</span>
                </div>

                <div v-for="(group, gi) in splitGroups" :key="gi"
                  class="rounded-xl border p-4 space-y-3"
                  :class="gi === 0 ? 'border-teal-200 bg-teal-50/30' : 'border-slate-200 bg-white'">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                      :class="gi === 0 ? 'bg-teal-200 text-teal-800' : 'bg-slate-200 text-slate-600'">
                      {{ String.fromCharCode(65 + gi) }}
                    </div>
                    <InputText v-model="group.customer_name"
                      :placeholder="'Pelanggan ' + String.fromCharCode(65 + gi)"
                      class="flex-1 text-sm" />
                    <Button v-if="splitGroups.length > 2" text rounded severity="danger"
                      size="small" @click="removeSplitGroup(gi)">
                      <template #icon>
                        <Trash2 class="w-4 h-4" stroke-width="1.5" />
                      </template>
                    </Button>
                  </div>

                  <div class="space-y-1.5">
                    <div v-for="item in selectedOrder.items" :key="item.id"
                      class="flex items-center gap-2 text-xs bg-white rounded-lg px-2.5 py-2 border border-slate-100"
                      :class="{ 'opacity-40': getSplitQty(item.id, gi) === 0 }">
                      <span class="flex-1 font-medium text-slate-700 truncate">{{ item.product_name }}</span>
                      <div class="flex items-center gap-1 shrink-0">
                        <button @click="adjustSplit(item.id, gi, -1)"
                          :disabled="getSplitQty(item.id, gi) <= 0"
                          class="w-6 h-6 rounded flex items-center justify-center font-bold text-xs transition-all"
                          :class="getSplitQty(item.id, gi) > 0
                            ? 'bg-red-100 text-red-600 hover:bg-red-200'
                            : 'bg-slate-50 text-slate-300 cursor-not-allowed'">-</button>
                        <span class="w-5 text-center font-bold text-sm tabular-nums text-slate-800">
                          {{ getSplitQty(item.id, gi) }}
                        </span>
                        <button @click="adjustSplit(item.id, gi, 1)"
                          :disabled="getSplitQty(item.id, gi) >= getRemainingQty(item.id, gi)"
                          class="w-6 h-6 rounded flex items-center justify-center font-bold text-xs transition-all"
                          :class="getSplitQty(item.id, gi) < getRemainingQty(item.id)
                            ? 'bg-teal-100 text-teal-700 hover:bg-teal-200'
                            : 'bg-slate-50 text-slate-300 cursor-not-allowed'">+</button>
                      </div>
                      <span class="w-12 text-right text-slate-400 tabular-nums shrink-0">/{{ item.qty }}</span>
                    </div>
                  </div>

                  <div class="flex justify-end text-sm font-semibold text-slate-700">
                    <span>Total: <span class="text-teal-600">{{ formatRupiah(computeGroupTotal(gi)) }}</span></span>
                  </div>
                </div>

                <Button label="+ Tambah Bagian" severity="secondary" text size="small"
                  @click="addSplitGroup" :disabled="splitGroups.length >= 6">
                  <template #icon>
                    <Plus class="w-4 h-4" stroke-width="1.5" />
                  </template>
                </Button>

                <div v-if="!isSplitValid" class="flex items-center gap-2 p-3 rounded-xl bg-amber-50 border border-amber-200">
                  <AlertTriangle class="w-5 h-5 text-amber-500 shrink-0" stroke-width="1.5" />
                  <p class="text-xs text-amber-700">Semua item harus terbagi habis ke semua bagian sebelum split.</p>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                  <Button label="Proses Split" severity="contrast"
                    :disabled="!isSplitValid || splitSaving"
                    :loading="splitSaving" @click="processSplit">
                    <template #icon>
                      <GitBranch class="w-4 h-4" stroke-width="1.5" />
                    </template>
                  </Button>
                </div>
              </div>
            </TabPanel>

            <!-- ══ TAB 3: Log ══ -->
            <TabPanel value="3">
              <div class="space-y-1.5">
                <div v-for="log in selectedOrder.logs" :key="log.id"
                  class="flex items-start gap-2 text-xs text-slate-500">
                  <Circle class="w-1.5 h-1.5 mt-1.5 text-slate-300 shrink-0" fill="currentColor" stroke-width="1.5" />
                  <div class="flex-1">
                    <span class="text-slate-700">{{ log.user?.name || 'System' }}</span>
                    <span v-if="log.note?.startsWith('refund:')" class="text-orange-600 ml-1">{{ log.note }}</span>
                    <span v-else-if="log.note?.startsWith('split')" class="text-violet-600 ml-1">{{ log.note }}</span>
                    <span v-else-if="log.note?.startsWith('merged')" class="text-blue-600 ml-1">{{ log.note }}</span>
                    <span v-else class="ml-1">
                      {{ statusLabel(log.to_status) }}
                      <span v-if="log.from_status">(dari {{ statusLabel(log.from_status) }})</span>
                    </span>
                    <span class="ml-2 text-slate-400">{{ formatDateTime(log.created_at) }}</span>
                  </div>
                </div>
                <div v-if="!selectedOrder.logs?.length" class="text-xs text-slate-400 italic">
                  Belum ada aktivitas
                </div>
              </div>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>
    </Dialog>

    <!-- Void Confirm Dialog -->
    <Dialog v-model:visible="showVoidConfirm" header="Void Pesanan" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <AlertTriangle class="w-5 h-5 text-red-500 shrink-0" stroke-width="1.5" />
          <p class="text-sm text-red-700">
            Yakin ingin void pesanan <strong>#{{ selectedOrder?.id }}</strong>? Tindakan ini tidak bisa dibatalkan.
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showVoidConfirm = false" />
          <Button label="Void" severity="danger" :loading="voidLoading" @click="voidOrder" />
        </div>
      </div>
    </Dialog>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { usePermission } from '../../utils/usePermission'
import { useAuthStore } from '../../stores/auth'
import { formatRupiah } from '../../utils/format'
import { useToastStore } from '../../stores/toast'
import { listenOrderUpdates } from '../../echo'
import client from '../../api/client'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import {
  RefreshCw, FlaskConical, ArrowUpCircle, X, XCircle, User,
  Receipt, Eye, Undo2, GitBranch, History, Plus, Trash2,
  AlertTriangle, Circle, Ban
} from 'lucide-vue-next'

const perm = usePermission()
const auth = useAuthStore()
const toast = useToastStore()

const loading = ref(true)
const orders = ref([])
const rowsPerPage = ref(10)
const seeding = ref(false)
const orderTab = ref('0')
let stopListeners = []

// Filters
const filterStatus = ref('')
const filterPayment = ref('')
const statusOptions = [
  { label: 'Semua Status', value: '' },
  { label: 'Draft', value: 'draft' },
  { label: 'Dikonfirmasi', value: 'confirmed' },
  { label: 'Dimasak', value: 'preparing' },
  { label: 'Selesai', value: 'done' },
  { label: 'Dibatalkan', value: 'cancelled' },
  { label: 'Void', value: 'voided' },
]
const paymentOptions = [
  { label: 'Semua Pembayaran', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Lunas', value: 'paid' },
  { label: 'Refund', value: 'refunded' },
]

// Selection for Merge
const selectedOrders = ref([])
const selectedOrderIds = computed(() => selectedOrders.value.map(o => o.id))

// Detail dialog
const dialogVisible = ref(false)
const selectedOrder = ref(null)

// Void confirm
const showVoidConfirm = ref(false)

// Refund
const refundQtys = ref({})
const refundReason = ref('')
const refundLoading = ref(false)
const voidLoading = ref(false)

// Split
const splitGroups = ref([])
const splitSaving = ref(false)

// Merge mode
const mergeCustomerName = ref('')
const mergeSaving = ref(false)

// --- Computed ---
const totalPaid = computed(() => {
  if (!selectedOrder.value?.payments) return 0
  return selectedOrder.value.payments.reduce((sum, p) => sum + p.amount, 0)
})

const refundableItems = computed(() => {
  return (selectedOrder.value?.items || []).filter(i => i.refundable_qty > 0)
})

const totalRefundAmount = computed(() => {
  if (!selectedOrder.value?.items) return 0
  let total = 0
  for (const item of selectedOrder.value.items) {
    const qty = refundQtys.value[item.id] || 0
    if (qty > 0) {
      total += Math.round((item.total_price / item.qty) * qty)
    }
  }
  return total
})

const isSplitValid = computed(() => {
  if (!selectedOrder.value?.items || splitGroups.value.length < 2) return false
  for (const item of selectedOrder.value.items) {
    let total = 0
    for (let gi = 0; gi < splitGroups.value.length; gi++) {
      total += getSplitQty(item.id, gi)
    }
    if (total !== item.qty) return false
  }
  return true
})

// --- Helpers ---
function canRefund(order) {
  return order.payment_status === 'paid' && order.refund_status !== 'full'
}

function canVoid(order) {
  return ['draft', 'confirmed'].includes(order.status)
}

function canSplit(order) {
  return ['draft', 'confirmed'].includes(order.status) && order.payment_status !== 'paid'
}

// --- Refund ---
function adjustRefund(itemId, delta) {
  const current = refundQtys.value[itemId] || 0
  const item = selectedOrder.value?.items?.find(i => i.id === itemId)
  if (!item) return
  const max = item.refundable_qty
  const next = Math.max(0, Math.min(max, current + delta))
  refundQtys.value = { ...refundQtys.value, [itemId]: next }
}

async function processRefund() {
  const items = []
  for (const item of selectedOrder.value.items) {
    const qty = refundQtys.value[item.id] || 0
    if (qty > 0 && qty <= item.refundable_qty) {
      items.push({ order_item_id: item.id, qty })
    }
  }
  if (items.length === 0) { toast.warning('Refund', 'Pilih minimal 1 item untuk di-refund'); return }

  refundLoading.value = true
  try {
    await client.post(`/orders/${selectedOrder.value.id}/refund`, { items, reason: refundReason.value })
    toast.success('Refund Berhasil', 'Item berhasil di-refund')
    dialogVisible.value = false
    await fetchOrders()
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors?.items?.[0] || 'Gagal refund'
    toast.error('Refund Gagal', msg)
  } finally { refundLoading.value = false }
}

function confirmVoid() {
  showVoidConfirm.value = true
}

async function voidOrder() {
  showVoidConfirm.value = false
  voidLoading.value = true
  try {
    await client.put(`/orders/${selectedOrder.value.id}/status`, { status: 'voided' })
    toast.success('Void Berhasil', 'Pesanan telah di-void')
    dialogVisible.value = false
    await fetchOrders()
  } catch (err) {
    toast.error('Void Gagal', err.response?.data?.message || 'Gagal void')
  } finally { voidLoading.value = false }
}

// --- Split Bill ---
function addSplitGroup() {
  if (splitGroups.value.length >= 6) return
  splitGroups.value.push({ customer_name: '', items: {} })
}

function removeSplitGroup(gi) {
  if (splitGroups.value.length <= 2) return
  const removed = splitGroups.value[gi]
  for (const itemId of Object.keys(removed.items)) {
    const qty = removed.items[itemId] || 0
    if (qty > 0) {
      splitGroups.value[0].items[itemId] = (splitGroups.value[0].items[itemId] || 0) + qty
    }
  }
  splitGroups.value.splice(gi, 1)
}

function getSplitQty(itemId, groupIndex) {
  return splitGroups.value[groupIndex]?.items[itemId] || 0
}

function getRemainingQty(itemId, groupIndex) {
  const item = selectedOrder.value?.items?.find(i => i.id === itemId)
  if (!item) return 0
  let totalAssigned = 0
  for (const group of splitGroups.value) {
    totalAssigned += group.items[itemId] || 0
  }
  const current = getSplitQty(itemId, groupIndex)
  return item.qty - totalAssigned + current
}

function adjustSplit(itemId, groupIndex, delta) {
  const current = getSplitQty(itemId, groupIndex)
  const item = selectedOrder.value?.items?.find(i => i.id === itemId)
  if (!item) return

  if (delta > 0) {
    let totalAssigned = 0
    for (const group of splitGroups.value) {
      totalAssigned += group.items[itemId] || 0
    }
    if (totalAssigned >= item.qty) return
  }

  const next = Math.max(0, current + delta)
  splitGroups.value[groupIndex].items = {
    ...splitGroups.value[groupIndex].items,
    [itemId]: next,
  }
}

function computeGroupTotal(groupIndex) {
  const group = splitGroups.value[groupIndex]
  if (!group || !selectedOrder.value?.items) return 0
  let total = 0
  for (const item of selectedOrder.value.items) {
    const qty = group.items[item.id] || 0
    if (qty > 0) {
      total += item.unit_price * qty
    }
  }
  return total
}

async function processSplit() {
  if (!isSplitValid.value) return

  splitSaving.value = true
  try {
    const splits = splitGroups.value.map(group => ({
      customer_name: group.customer_name || null,
      items: Object.entries(group.items)
        .filter(([, qty]) => qty > 0)
        .map(([itemId, qty]) => ({ order_item_id: parseInt(itemId), qty })),
    })).filter(s => s.items.length > 0)

    const { data } = await client.post(`/orders/${selectedOrder.value.id}/split`, { splits })
    const count = data.data.length
    toast.success('Split Berhasil', `${count} tagihan baru dibuat`)
    dialogVisible.value = false
    await fetchOrders()
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors?.splits?.[0] || 'Gagal split'
    toast.error('Split Gagal', msg)
  } finally {
    splitSaving.value = false
  }
}

// ────── Merge Preview (computed) ──────
const mergePreviewItems = computed(() => {
  const itemMap = {}
  for (const o of selectedOrders.value) {
    for (const item of o.items || []) {
      const key = item.product_name
      if (!itemMap[key]) itemMap[key] = { name: key, qty: 0, total: 0 }
      itemMap[key].qty += item.qty
      itemMap[key].total += item.total_price
    }
  }
  return Object.values(itemMap)
})

const mergePreviewTotal = computed(() => {
  return selectedOrders.value.reduce((sum, o) => sum + (o.grand_total || 0), 0)
})

function removeFromMerge(order) {
  selectedOrders.value = selectedOrders.value.filter(o => o.id !== order.id)
}

// --- Merge Bill ---
async function processMerge() {
  mergeSaving.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id

    const { data } = await client.post('/orders/merge', {
      outlet_id: outletId,
      order_ids: selectedOrderIds.value,
      customer_name: mergeCustomerName.value || null,
    })
    toast.success('Merge Berhasil', `Pesanan #${data.data.id} dibuat`)
    selectedOrders.value = []
    await fetchOrders()
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors?.orders?.[0] || 'Gagal merge'
    toast.error('Merge Gagal', msg)  }
  finally {
    mergeSaving.value = false
    selectedOrders.value = []
  }
}

// --- Reset ---
function resetModes() {
  refundQtys.value = {}
  refundReason.value = ''
  splitGroups.value = []
}

// --- Status helpers ---
function statusLabel(s) {
  const map = { draft: 'Draft', confirmed: 'Dikonfirmasi', preparing: 'Dimasak', done: 'Selesai', cancelled: 'Batal', voided: 'Void' }
  return map[s] || s
}
function statusSeverity(s) {
  const map = { draft: 'info', confirmed: 'warn', preparing: 'warn', done: 'success', cancelled: 'danger', voided: 'danger' }
  return map[s] || 'info'
}
function paymentLabel(order) {
  if (order.refund_status === 'full') return 'Refund Full'
  if (order.refund_status === 'partial') return 'Refund Sebagian'
  if (order.payment_status === 'paid') return 'Lunas'
  return 'Pending'
}
function paymentSeverity(order) {
  if (order.refund_status === 'full') return 'danger'
  if (order.refund_status === 'partial') return 'warn'
  if (order.payment_status === 'paid') return 'success'
  return 'warning'
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
function formatDateTime(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  })
}

// --- Data fetching ---
async function fetchOrders() {
  loading.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return

    const params = { outlet_id: outletId, per_page: 50 }
    if (filterStatus.value) params.status = filterStatus.value
    if (filterPayment.value) params.payment_status = filterPayment.value

    if (filterPayment.value === 'refunded') {
      delete params.payment_status
    }

    const { data } = await client.get('/orders', { params })
    let result = data.data
    if (filterPayment.value === 'refunded') {
      result = result.filter(o => o.refund_status)
    }
    orders.value = result
  } catch (_) {
  } finally { loading.value = false }
}

async function openDetail(order) {
  try {
    const { data } = await client.get(`/orders/${order.id}`)
    selectedOrder.value = data.data
    resetModes()
    const qtyMap = {}
    for (const item of selectedOrder.value.items) {
      qtyMap[item.id] = item.refundable_qty
    }
    refundQtys.value = qtyMap
    refundReason.value = ''
    splitGroups.value = [
      { customer_name: '', items: {} },
      { customer_name: '', items: {} },
    ]
    if (selectedOrder.value?.items) {
      for (const item of selectedOrder.value.items) {
        splitGroups.value[0].items[item.id] = item.qty
      }
    }
    orderTab.value = '0'
    dialogVisible.value = true
  } catch (_) { toast.error('Gagal', 'Gagal memuat detail pesanan') }
}

// --- Seed Test Data ---
async function seedTestOrder() {
  seeding.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outlet = outletRes.data[0]
    if (!outlet) { toast.warning('Sample Order', 'Tidak ada outlet. Buat outlet dulu!'); return }

    const { data: prodRes } = await client.get('/products', {
      params: { outlet_id: outlet.id }
    })
    const products = prodRes.data
    if (products.length < 2) {
      toast.warning('Sample Order', 'Butuh minimal 2 produk. Tambah menu dulu!')
      return
    }

    const shuffled = [...products].sort(() => Math.random() - 0.5)
    const selected = shuffled.slice(0, 3)

    const { data: orderRes } = await client.post('/orders', {
      outlet_id: outlet.id,
      customer_name: 'Sample ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    })
    const order = orderRes.data

    for (let i = 0; i < selected.length; i++) {
      const p = selected[i]
      const qty = Math.floor(Math.random() * 3) + 1
      await client.post(`/orders/${order.id}/items`, {
        product_id: p.id,
        product_name: p.name,
        qty,
        unit_price: p.price,
        notes: i === 0 ? 'Extra pedas' : null,
      })
    }

    if (Math.random() > 0.3) {
      await client.put(`/orders/${order.id}/status`, { status: 'confirmed' })
    }

    toast.success('Sample Order', `Order #${order.id} berhasil dibuat dengan ${selected.length} item`)
    await fetchOrders()
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Gagal buat sample'
    toast.error('Sample Gagal', msg)
  } finally {
    seeding.value = false
  }
}

function handleOrderUpdate(payload) {
  const existing = orders.value.find((o) => o.id === payload.id)
  if (existing) {
    existing.status = payload.status
    existing.payment_status = payload.payment_status
    toast.info('Pesanan diperbarui', `Order #${payload.id} — ${statusLabel(payload.status)}`)
  } else {
    toast.info('Pesanan baru masuk', `Order #${payload.id}${payload.table_id ? ' dari meja' : ''}`)
    fetchOrders()
  }
}

onMounted(() => {
  fetchOrders()
  stopListeners = auth.outletIds.map((outletId) => listenOrderUpdates(outletId, handleOrderUpdate))
})

onUnmounted(() => {
  stopListeners.forEach((stop) => stop())
})
</script>

<style scoped>
.merge-slide-enter-active {
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.merge-slide-leave-active {
  transition: all 0.2s ease-in;
}
.merge-slide-enter-from {
  opacity: 0;
  transform: translateY(-16px) scale(0.96);
}
.merge-slide-leave-to {
  opacity: 0;
  transform: translateY(-12px) scale(0.96);
}
</style>
