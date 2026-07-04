<template>
    <div class="thin-modern-scrollbar mt-2 min-h-0 flex-1 overflow-y-auto overscroll-contain p-2">
        <slot />
    </div>
</template>

<style scoped>
.thin-modern-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(156 163 175 / 0.55) transparent;
}

.thin-modern-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.thin-modern-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.thin-modern-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(156 163 175 / 0.45);
    border-radius: 999px;
}

.thin-modern-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(107 114 128 / 0.75);
}
</style>
