<template>
    <Multiselect ref="vselectRef" v-model="proxyModel" :options="formattedOptions" :multiple="multiple"
        :searchable="true" :clear-on-select="!multiple" :close-on-select="!multiple" :placeholder="placeholder"
        :class="{ 'is-invalid': error }" label="label" track-by="value" @search-change="onSearchDebounced"
        @open="onDropdownOpen">
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
import { ref, computed, onMounted, watch, nextTick } from "vue"
import Multiselect from "vue-multiselect"
import "vue-multiselect/dist/vue-multiselect.min.css"
import { BSpinner } from "bootstrap-vue-next"

const {
    selectedItem = null,
    fieldName,
    form,
    dataList = [],
    error = null,
    size = "sm",
    clearable = true,
    multiple = false,
    debounce = 300,
    placeholder = "Select",
    selectedLabelKey = "text",
    selectedValueKey = "value",
    dataListLabelKey = "text",
    dataListValueKey = "value",
} = defineProps({
    selectedItem: { type: [String, Number, Object, Array], default: null },
    fieldName: { type: String, required: true },
    form: { type: Object, required: true },
    dataList: { type: [Array, Object], default: () => [] },
    error: { type: [String, Boolean], default: null },
    size: { type: String, default: "sm" },
    clearable: { type: Boolean, default: true },
    multiple: { type: Boolean, default: false },
    debounce: { type: Number, default: 300 },
    placeholder: { type: String, default: "Select" },
    selectedLabelKey: { type: String, default: "text" },
    selectedValueKey: { type: String, default: "value" },
    dataListLabelKey: { type: String, default: "text" },
    dataListValueKey: { type: String, default: "value" },
})

const options = ref([])
const loadingMore = ref(false)
const page = ref(1)
const perPage = 10
const lastPage = ref(1)
const searchQuery = ref("")
const vselectRef = ref(null)
let searchTimeout = null
const proxyModel = ref(null)

const allItems = Array.isArray(dataList)
    ? dataList.filter(Boolean)
    : Object.values(dataList).filter(Boolean)

const formattedOptions = computed(() =>
    options.value.map(item => ({
        label: item[dataListLabelKey] ?? "",
        value: item[dataListValueKey] ?? null,
        raw: item
    }))
)

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

const normalizeItem = item => {
    if (!item) return multiple ? [] : null

    if (typeof item === "object") {
        if (multiple && Array.isArray(item)) {
            return item.map(v => ({
                label: v[selectedLabelKey] ?? "",
                value: v[selectedValueKey] ?? null,
                raw: v
            }))
        } else {
            return {
                label: item[selectedLabelKey] ?? "",
                value: item[selectedValueKey] ?? null,
                raw: item
            }
        }
    }

    const found = allItems.find(i => i[dataListValueKey] == item)

    return found
        ? {
            label: found[selectedLabelKey] ?? "",
            value: found[dataListValueKey],
            raw: found
        }
        : multiple
            ? []
            : null
}

watch(proxyModel, val => updateForm(val), { deep: true })

const fetchPage = (pageNumber = 1, reset = false) => {
    const start = (pageNumber - 1) * perPage

    const filtered = allItems.filter(item => {
        const label = item[dataListLabelKey]
        return (
            typeof label === "string" &&
            label.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
    })

    lastPage.value = Math.max(1, Math.ceil(filtered.length / perPage))

    const pageItems = filtered.slice(start, start + perPage)

    if (reset) {
        options.value = pageItems
    } else {
        options.value.push(...pageItems)
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

    const dropdown = vselectRef.value?.$el.querySelector(
        ".multiselect__content-wrapper"
    )

    if (!dropdown) return

    const prevScrollTop = dropdown.scrollTop

    page.value++
    fetchPage(page.value, false)

    nextTick(() => {
        dropdown.scrollTop = prevScrollTop
    })
}

const handleScroll = e => {
    const el = e.target

    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 5) {
        loadMoreManual()
    }
}

const onDropdownOpen = () => {
    nextTick(() => {
        const dropdown = vselectRef.value?.$el.querySelector(
            ".multiselect__content-wrapper"
        )

        if (!dropdown) return

        dropdown.removeEventListener("scroll", handleScroll)
        dropdown.addEventListener("scroll", handleScroll)
    })
}

onMounted(() => {
    proxyModel.value = normalizeItem(selectedItem)
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
