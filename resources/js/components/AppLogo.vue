<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name ?? 'Sonarya';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-white shadow-xs border border-slate-200/80 p-1 shrink-0 overflow-hidden select-none"
    >
        <AppLogoIcon class="size-full" />
    </div>
    <div class="ml-1.5 grid flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
        <span class="truncate leading-tight font-bold tracking-tight text-foreground">
            {{ name }}
        </span>
        <span class="truncate text-[10px] font-medium text-muted-foreground leading-none mt-0.5">
            Registre SEP
        </span>
    </div>
</template>
