<template>
    <div class="thin-modern-scrollbar mt-2 min-h-0 flex-1 overflow-y-auto overscroll-contain p-2">
        <slot />
    </div>
</template>

<style scoped>
.thin-modern-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: var(--news-scrollbar-thumb) transparent;
}

.thin-modern-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.thin-modern-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.thin-modern-scrollbar::-webkit-scrollbar-thumb {
    background: var(--news-scrollbar-thumb-soft);
    border-radius: 999px;
}

.thin-modern-scrollbar::-webkit-scrollbar-thumb:hover {
    background: var(--news-scrollbar-thumb-hover);
}
</style>
