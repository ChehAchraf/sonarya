<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

defineProps<Props>();
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList class="flex-nowrap overflow-hidden">
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem
                    :class="[
                        breadcrumbs.length > 2 && index < breadcrumbs.length - 2
                            ? 'hidden sm:inline-flex'
                            : 'inline-flex',
                    ]"
                >
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage class="truncate max-w-[130px] sm:max-w-xs md:max-w-md font-medium text-foreground">
                            {{ item.title }}
                        </BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link :href="item.href" class="truncate max-w-[90px] sm:max-w-[140px] md:max-w-none">
                                {{ item.title }}
                            </Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator
                    v-if="index !== breadcrumbs.length - 1"
                    :class="[
                        breadcrumbs.length > 2 && index < breadcrumbs.length - 2
                            ? 'hidden sm:inline-flex'
                            : 'inline-flex',
                    ]"
                />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
