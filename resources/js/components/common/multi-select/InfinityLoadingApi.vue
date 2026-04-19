<template>
    <Multiselect
        ref="vselectRef"
        v-model="proxyModel"
        :options="formattedOptions"
        :multiple="multiple"
        :loading="loading"
        :searchable="true"
        :clear-on-select="!multiple"
        :close-on-select="!multiple"
        :placeholder="placeholder"
        label="label"
        track-by="value"
        @search-change="onSearchDebounced"
        @open="onDropdownOpen"
        :class="{ 'is-invalid': error }"
    >
        <template #loading>
            <div class="text-center py-1">
                <BSpinner small />
            </div>
        </template>

        <template #afterList>
            <div class="text-center py-1 text-xs text-gray-400">
                Page {{ page }} / {{ lastPage }}
            </div>
        </template>
    </Multiselect>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from "vue"
import axios from "axios"
import Multiselect from "vue-multiselect"
import "vue-multiselect/dist/vue-multiselect.min.css"
import { BSpinner } from "bootstrap-vue-next"

const {
    selectedItem = null,
    fieldName,
    form,
    apiUrl,
    error = null,
    size = "sm",
    clearable = true,
    multiple = false,
    debounce = 300,
    placeholder = "Select",
    selectedLabelKey = "name",
    selectedValueKey = "id",
    apiLabelKey = "name",
    apiValueKey = "id"
} = defineProps({
    selectedItem: { type: [String, Number, Object, Array], default: null },
    fieldName: { type: String, required: true },
    form: { type: Object, required: true },
    apiUrl: { type: String, required: true },
    error: { type: [String, Boolean], default: null },
    size: { type: String, default: "sm" },
    clearable: { type: Boolean, default: true },
    multiple: { type: Boolean, default: false },
    debounce: { type: Number, default: 300 },
    placeholder: { type: String, default: "Select" },
    selectedLabelKey: { type: String, default: "name" },
    selectedValueKey: { type: String, default: "id" },
    apiLabelKey: { type: String, default: "name" },
    apiValueKey: { type: String, default: "id" },
})

const options = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const page = ref(1)
const lastPage = ref(1)
const searchQuery = ref("")
const vselectRef = ref(null)
let searchTimeout = null
const proxyModel = ref(null)

const formattedOptions = computed(() =>
    options.value.map(item => ({
        label: item[apiLabelKey],
        value: item[apiValueKey],
        raw: item
    }))
)

const normalizeItems = raw => (!raw ? [] : Array.isArray(raw) ? raw : Object.values(raw))

const updateForm = val => {
    if (!form || !fieldName) return
    if (multiple) {
        form[fieldName] = Array.isArray(val)
            ? val.map(v => v.raw?.[selectedValueKey] ?? v.value ?? v)
            : []
    } else {
        form[fieldName] = val
            ? val.raw?.[selectedValueKey] ?? val.value ?? val
            : null
    }
}

const normalizeItem = async item => {
    if (!item) return multiple ? [] : null
    if (typeof item === "object") {
        if (multiple && Array.isArray(item)) {
            return item.map(v => ({
                label: v[selectedLabelKey] ?? v[apiLabelKey],
                value: v[selectedValueKey] ?? v[apiValueKey],
                raw: v
            }))
        } else {
            return {
                label: item[selectedLabelKey] ?? item[apiLabelKey],
                value: item[selectedValueKey] ?? item[apiValueKey],
                raw: item
            }
        }
    }
    return await fetchItemByValue(item)
}

const fetchItemByValue = async value => {
    let found = null
    let p = 1
    do {
        const res = await axios.get(apiUrl, {
            params: { search: value, page: p }
        })
        const items = normalizeItems(res.data.items)
        lastPage.value = res.data.last_page || 1
        found = items.find(i => i[apiValueKey] == value)
        if (found) break
        p++
    } while (p <= lastPage.value)

    if (found) {
        return {
            label: found[selectedLabelKey] ?? found[apiLabelKey],
            value: found[selectedValueKey] ?? found[apiValueKey],
            raw: found
        }
    }
    return multiple ? [] : null
}

watch(proxyModel, val => updateForm(val), { deep: true })

const fetchPage = async (pageNumber = 1, reset = false) => {
    if (loading.value || loadingMore.value) return
    if (!reset && pageNumber > lastPage.value) return

    const dropdown = vselectRef.value?.$el.querySelector(".multiselect__content-wrapper")
    const scrollTop = dropdown ? dropdown.scrollTop : 0

    reset ? (loading.value = true) : (loadingMore.value = true)

    try {
        const res = await axios.get(apiUrl, {
            params: { search: searchQuery.value, page: pageNumber }
        })
        const data = normalizeItems(res.data.items)
        lastPage.value = res.data.last_page || 1
        if (reset) options.value = data
        else options.value.push(...data)
    } finally {
        setTimeout(() => {
            loading.value = false
            loadingMore.value = false
            if (dropdown && !reset) dropdown.scrollTop = scrollTop
        }, 300)
    }
}

const onSearchDebounced = search => {
    searchQuery.value = search
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        page.value = 1
        fetchPage(1, true)
    }, debounce)
}

const loadMoreManual = () => {
    if (loadingMore.value || page.value >= lastPage.value) return
    page.value++
    fetchPage(page.value, false)
}

const handleScroll = e => {
    const el = e.target
    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 5) {
        loadMoreManual()
    }
}

const onDropdownOpen = () => {
    nextTick(() => {
        const dropdown = vselectRef.value?.$el.querySelector(".multiselect__content-wrapper")
        if (!dropdown) return
        dropdown.removeEventListener("scroll", handleScroll)
        dropdown.addEventListener("scroll", handleScroll)
    })
}

onMounted(async () => {
    const normalized = await normalizeItem(selectedItem)
    proxyModel.value = normalized
    updateForm(proxyModel.value)
    fetchPage(1, true)
})
</script>

<style scoped>
:deep(.multiselect__content-wrapper) {
    max-height: 250px;
    overflow-y: auto;
}
</style>
