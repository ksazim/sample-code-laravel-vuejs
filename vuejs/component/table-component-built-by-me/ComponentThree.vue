<script setup>
import { ref, defineProps, defineEmits, onMounted, computed, watch, onBeforeUnmount  } from "vue";
import Success from "@/components/modal/notification/SuccessComponent";
import Warning from "@/components/modal/notification/WarningComponent";

const emit = defineEmits(["paginate", "removeRow"]);
const paginate = ref(10);

// const confirmation = ref(false);
const modal = ref(false);
const modalData = ref({});
const confirmation = ref(false)

const props = defineProps({
  list: Object,
  columns: Array,
  actions: Array,
  confirmationMsg: String,
  pagination: String,
});

const dropdownOpen = ref(false);

function closeDropdown(event) {
  if (!event.target.closest('.dropdown')) {
    dropdownOpen.value = false;
  }
}

const options = [10, 25, 50, 75, 100];


function removeRow(data) {
  modal.value = true;
  modalData.value = data;
}

async function axeRow(id) {
    modal.value = false
    emit('removeRow', id)
    confirmation.value = true
}

function closeModal() {
    modal.value = ''
    confirmation.value = ''
}


onMounted(() => {
  console.log(props.list);
  if (props.pagination && props.pagination.per_page) {
    paginate.value = props.pagination.per_page;
  }
  window.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
  window.removeEventListener('click', closeDropdown);
});

const visiblePageNumbers = computed(() => {
  if (!props.pagination) return [];
  
  const current = props.pagination.current_page;
  const last = props.pagination.last_page;
  const delta = 2;
  const range = [];
  const rangeWithDots = [];
  let l;

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i);
  }
  if (range[0] > 2) {
    range.unshift(1);
  }
  if (range[range.length - 1] < last - 1) {
    range.push(last);
  }

  for (let i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(i);
    l = i;
  }

  return rangeWithDots;
});

const showEllipsis = computed(() => {
  return visiblePageNumbers.value.includes('...');
});

function paginateTable(itemsPerPage, pageNumber = null) {
  emit("paginate", { 
    item_no: itemsPerPage, 
    page_no: pageNumber ? pageNumber : `?page=1`
  });
}

watch(paginate, (newValue) => {
  paginateTable(newValue);
});

</script>

<template>
  <div v-if="props.list" class="table-container">
    <table id="table">
      <tr>
        <th v-for="column in columns" :key="column">{{ column }}</th>
        <th v-if="actions">Action</th>
      </tr>
      <tr v-for="(data, index) in list" :key="index">
        <td>{{ index+1 }}</td>
        <td class="center" v-for="(value, key) in Object.entries(data).filter(([key, value]) => key !== 'id')" :key="key">
          <span v-if="value" v-html="value[1]"></span>
          <span v-else>-</span>
        </td>
        <td v-if="actions" class="action items-center">
            <!-- <span></span> -->
            <span v-for="action in actions" :key="action">
              <router-link class="action-btn" v-if="data && action.edit == true" :to="action.link + data.id">
                <font-awesome-icon style="color: #00bcd4" :icon="['fas', 'pen-to-square']" />
              </router-link>
              <font-awesome-icon class="action-btn" v-if="action.delete == true" @click="removeRow(data)" style="color: #ff4949" :icon="['fas', 'trash']" />
              <router-link class="action-btn" v-if="data && action.view == true" :to="action.link + data.id">
                <font-awesome-icon style="color: #49adff" :icon="['fas', 'eye']"/>
              </router-link>
              <router-link class="action-btn" v-if="data && action.settings == true" :to="action.link + data.id">
                <font-awesome-icon style="color: #49adff" :icon="['fas', 'gear']"/>
              </router-link>
              <router-link class="action-btn" v-if="data && action.invoice == true" :to="action.link + data.id">
                <font-awesome-icon style="color: #49ff49ff" :icon="['fas', 'receipt']"/>
              </router-link>
            </span>
        </td>
      </tr>
    </table>

    <div v-if="props.pagination" class="table-foote">
      <div class="entries-info">
        <div class="range-info">
          <span v-if="props.pagination['from'] != null" class="">
            Showing {{ props.pagination['from'] }} - {{ props.pagination['to'] }} of {{ props.pagination['total'] }} Entries
          </span>
          <span v-else>No results found </span>
        </div>
        
        <div class="entries-select">
          <label for="entries-per-page">Show</label>
          <select id="entries-per-page" v-model="paginate" class="primary-select">
            <option v-for="option in options" :key="option" :value="option">
              {{ option }}
            </option>
          </select>
          <label for="entries-per-page">Entries</label>
        </div>
      </div>
      <div v-if="props.pagination['links']" class="pagination">
          <button class="paginate-btn" :disabled="!props.pagination['links'].prev" @click="paginateTable(paginate, `?page=${props.pagination.current_page - 1}`)">
            <font-awesome-icon :icon="['fas', 'chevron-left']" />
          </button>

          <button v-for="page in visiblePageNumbers" :key="page" class="paginate-btn" :class="{ active: page === props.pagination.current_page }" @click="paginateTable(paginate, `?page=${page}`)">
            {{ page }}
          </button>

          <button v-if="showEllipsis" class="paginate-btn ellipsis" disabled>
            ...
          </button>

          <button class="paginate-btn" :disabled="!props.pagination['links'].next" @click="paginateTable(paginate, `?page=${props.pagination.current_page + 1}`)">
            <font-awesome-icon :icon="['fas', 'chevron-right']" />
          </button>
        </div>
    </div> 
  </div> 
  <Success @off-modal="closeModal" v-if="confirmation==true">
      <div>
          <p>{{ confirmationMsg }}</p>
      </div>
  </Success>
  <Warning @off-modal="closeModal" v-if="modal==true">
      <div>
          <!-- <p>Are you sure you want to delete "{{ (modalData.title) ? modalData.title : modalData.name }}"</p> -->
          <p>Are you sure you want to delete This Item ?</p>
          <div class="actions">
              <button @click="closeModal" class="cancel-btn-gray">Cancel</button>
              <button @click="axeRow(modalData.id)" class="cancel-btn">Yes Please</button>
          </div>
      </div>
  </Warning>
 
</template>

<style scoped>


.dropdown-menu {
  display: none;
  position: absolute;
  background-color: #f9f9f9;
  min-width: 160px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
  right: 0; 
  left: auto; 
  top: 100%; 
  margin-top: 2px; 
}

.dropdown-menu.show {
  display: block;
}

.entries-info {
  font-size: 14px;
  color: #272727;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #e3e7e9;
  padding: 15px;
  box-sizing: border-box;
}

.entries-select {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 14px;
  color: #272727;
}

.dropdown-item:hover {
  background-color: #f1f1f1;
}

.dropdown-item {
  color: black;
  padding: 12px 16px;
  text-decoration: none;
  display: block;
  white-space: nowrap; 
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  padding: 15px;
  box-sizing: border-box;
  border-top: 1px solid #e3e7e9;
}

.paginate-btn {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  background-color: #ffffff;
  color: #4a5568;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: all 0.2s;
}

.paginate-btn:hover:not(:disabled) {
  background-color: #edf2f7;
}

.paginate-btn.active {
  background-color: #4299e1;
  color: #ffffff;
  border-color: #4299e1;
}

.paginate-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.paginate-btn.ellipsis {
  border: none;
  background-color: transparent;
}

.primary-select {
  padding: 0.4rem 0.75rem;
  border: 1px solid #bec0c4;
  border-radius: 0.25rem;
  background-color: #ffffff;
  outline: none;
  cursor: pointer;
}
.loader-position {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}

.table-container {
  display: grid;
  font-size: 11px;
}

.dropdown{
  position: relative;
  display: inline-block;
}

.export {
  display: flex;
  gap: 20px;
}

.cursor  {
  cursor: pointer;
}

.export > *:hover {
  opacity: 0.8;
}

.table-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-dots {
  display: flex;
  align-items: center;
  gap: 5px;
}

.pagination-dots > .dot {
  height: 5px;
  width: 5px;
  border-radius: 50%;
  background: gray;
}

table {
  border-collapse: collapse;
  width: 100%;
  overflow-x: scroll;
  font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
}

table td {
  padding: 6px;
  vertical-align: middle !important;
  font-size: 14px;
  /* height: 100% !important; */
  padding: 20px;
  font-weight: 100;
  box-sizing: border-box;
}

table th {
  border-bottom: 1px solid #e3e7e9;
  font-weight: 700;
  /* background-color: #f2f4f7; */
  padding: 20px;
  box-sizing: border-box;
  font-size: 15px;

}

table .action {
  justify-content: center;
  align-items: center;
  gap: 15px;

}

table .action > *:hover {
  opacity: 0.8;
}

table > td {
  height: 100%;
  display: flex !important;
  justify-content: center !important;
  flex-direction: column;
} 

.actions {
  margin: 20px 0 0 0;
  display: flex;
  justify-content: center;
  gap: 15px;
}

.action-btn:hover {
  cursor: pointer;
}
.actions > button {
  width: 100%;
}

th:first-child,
td:first-child {
  width: 20px !important; /* or set a specific width like width: 50px; */
}

.center > span {
  display: flex;
  justify-content: center;
  align-items: center;
}

.items-center {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
