<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faInfo, faPen } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faInfo)

const { t } = useTranslate()

const { menu } = defineProps({
    menu: { type: Object, required: true },
})
</script>

<template>
    <div>

        <div v-if="menu?.menu_items && menu?.menu_items?.length">

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm">
                <table class="min-w-full text-sm text-left">

                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-2">
                                {{ t("table.columns.sl") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("table.columns.name") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("table.columns.language") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("table.columns.parent") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("table.columns.position") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("table.columns.created_at") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("table.columns.action") }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(item, index) in menu?.menu_items" :key="item.id" class="border-t hover:bg-gray-50">

                            <td class="px-4 py-2">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-2">
                                {{ item?.name }}
                            </td>

                            <td class="px-4 py-2">
                                {{ item.language?.name }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.parent?.name || "N/A" }}
                            </td>

                            <td class="px-4 py-2">
                                {{ item?.position || "N/A" }}
                            </td>

                            <td class="px-4 py-2">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-2">
                                <a :href="route('back-office.menus.menu-items.details', { slug: menu.slug, menuItemSlug: item.slug })"
                                    class="inline-flex items-center gap-1 px-2 py-1 m-1 text-xs border border-blue-500 text-blue-500 rounded hover:bg-blue-50">
                                    <FontAwesomeIcon icon="info" />
                                    {{ t("table.menus.details") }}
                                </a>

                                <a :href="route('back-office.menus.menu-items.edit', { slug: menu.slug, menuItemSlug: item.slug })"
                                    class="inline-flex items-center gap-1 px-2 py-1 m-1 text-xs border border-yallow-500 text-yallow-500 rounded hover:bg-yallow-50">
                                    <FontAwesomeIcon icon="pen" />
                                    {{ t("table.menus.edit") }}
                                </a>
                            </td>

                        </tr>
                    </tbody>

                </table>
            </div>

            <div class="flex justify-center mt-4">
                <a :href="route('back-office.menus.menu-items.index', { slug: menu?.slug })"
                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-400 text-gray-600 rounded hover:bg-gray-100">
                    {{ t("layout_menus.show_all") }}
                </a>
            </div>

        </div>

        <div v-else>
            <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded">
                {{ t("labels.no_record_found") }}
            </div>
        </div>

    </div>
</template>
