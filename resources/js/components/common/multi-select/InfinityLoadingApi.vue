<template>
    <div class="multi-select-cointainer" :class="props.error ? 'border border-red-500 rounded-md' : ''">
        <Multiselect ref="vselectRef" v-model="proxyModel" :options="formattedOptions" :multiple="props.multiple"
            :loading="loading" :searchable="true" :clear-on-select="!props.multiple" :close-on-select="!props.multiple"
            :placeholder="props.placeholder" label="label" track-by="value" @search-change="onSearchDebounced"
            @open="onDropdownOpen">
            <template #afterList>
                <div v-if="loadingMore" class="text-center py-2 text-xs text-gray-400">
                    Loading more...
                </div>
                <div v-else class="text-center py-1 text-xs text-gray-400">
                    Page {{ page }} / {{ lastPage }}
                </div>
            </template>
        </Multiselect>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from "vue"
import axios from "axios"
import Multiselect from "vue-multiselect"
import "vue-multiselect/dist/vue-multiselect.css"

const props = defineProps({
    selectedItem: { type: [String, Number, Object, Array], default: null },
    fieldName: { type: String, required: true },
    form: { type: Object, required: true },
    apiUrl: { type: String, required: true },
    error: { type: [String, Boolean], default: null },
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
const proxyModel = ref(props.multiple ? [] : null)

let searchTimeout = null

const formattedOptions = computed(() =>
    options.value.map(item => ({
        label: item?.[props.apiLabelKey] ?? "",
        value: item?.[props.apiValueKey] ?? null,
        raw: item
    }))
)

const normalizeItems = raw =>
    !raw ? [] : Array.isArray(raw) ? raw : Object.values(raw)

const updateForm = val => {
    if (!props.form || !props.fieldName) return

    if (props.multiple) {
        props.form[props.fieldName] = Array.isArray(val)
            ? val.map(v => v?.raw?.[props.selectedValueKey] ?? v?.value ?? v)
            : []
    } else {
        props.form[props.fieldName] = val
            ? val?.raw?.[props.selectedValueKey] ?? val?.value ?? val
            : null
    }
}

const normalizeItem = async item => {
    if (!item) return props.multiple ? [] : null

    if (typeof item === "object") {
        if (props.multiple && Array.isArray(item)) {
            return item.map(v => ({
                label: v?.[props.selectedLabelKey] ?? v?.[props.apiLabelKey] ?? "",
                value: v?.[props.selectedValueKey] ?? v?.[props.apiValueKey] ?? null,
                raw: v
            }))
        }

        return {
            label: item?.[props.selectedLabelKey] ?? item?.[props.apiLabelKey] ?? "",
            value: item?.[props.selectedValueKey] ?? item?.[props.apiValueKey] ?? null,
            raw: item
        }
    }

    return await fetchItemByValue(item)
}

const fetchItemByValue = async value => {
    let found = null
    let p = 1
    let totalPages = 1

    do {
        const res = await axios.get(props.apiUrl, {
            params: { search: value, page: p }
        })

        const items = normalizeItems(res.data?.items)

        totalPages = res.data?.last_page || 1
        found = items.find(i => i?.[props.apiValueKey] == value)

        if (found) break

        p++
    } while (p <= totalPages)

    if (!found) return props.multiple ? [] : null

    return {
        label: found?.[props.selectedLabelKey] ?? found?.[props.apiLabelKey] ?? "",
        value: found?.[props.selectedValueKey] ?? found?.[props.apiValueKey] ?? null,
        raw: found
    }
}

const fetchPage = async (pageNumber = 1, reset = false) => {
    if (loading.value || loadingMore.value) return
    if (!reset && pageNumber > lastPage.value) return

    const dropdown =
        vselectRef.value?.$el?.querySelector(".multiselect__content-wrapper")

    const scrollTop = dropdown ? dropdown.scrollTop : 0

    if (reset) {
        loading.value = true
    } else {
        loadingMore.value = true
    }

    try {
        const res = await axios.get(props.apiUrl, {
            params: {
                search: searchQuery.value,
                page: pageNumber
            }
        })

        const data = normalizeItems(res.data?.items)

        lastPage.value = res.data?.last_page || 1

        if (reset) {
            options.value = data
        } else {
            options.value = [...options.value, ...data]
        }
    } finally {
        loading.value = false
        loadingMore.value = false

        nextTick(() => {
            if (dropdown && !reset) {
                dropdown.scrollTop = scrollTop
            }
        })
    }
}

const resetAndFetch = async () => {
    options.value = []
    proxyModel.value = props.multiple ? [] : null
    searchQuery.value = ""
    page.value = 1
    lastPage.value = 1

    updateForm(proxyModel.value)

    await fetchPage(1, true)
}

watch(proxyModel, val => {
    updateForm(val)
}, { deep: true })

watch(
    () => props.apiUrl,
    async () => {
        await resetAndFetch()
    }
)

const onSearchDebounced = search => {
    searchQuery.value = search

    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        page.value = 1
        lastPage.value = 1
        fetchPage(1, true)
    }, props.debounce)
}

const loadMoreManual = async () => {
    if (loading.value || loadingMore.value) return
    if (page.value >= lastPage.value) return

    const nextPage = page.value + 1

    await fetchPage(nextPage, false)

    page.value = nextPage
}

const handleScroll = e => {
    const el = e.target

    if (
        el.scrollTop + el.clientHeight >= el.scrollHeight - 20 &&
        !loading.value &&
        !loadingMore.value &&
        page.value < lastPage.value
    ) {
        loadMoreManual()
    }
}

const onDropdownOpen = () => {
    nextTick(() => {
        const dropdown =
            vselectRef.value?.$el?.querySelector(".multiselect__content-wrapper")

        if (!dropdown) return

        dropdown.removeEventListener("scroll", handleScroll)
        dropdown.addEventListener("scroll", handleScroll)
    })
}

onMounted(async () => {
    const normalized = await normalizeItem(props.selectedItem)

    proxyModel.value = normalized

    updateForm(proxyModel.value)

    await fetchPage(1, true)
})
</script>


