<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        xmlns:xlink="http://www.w3.org/1999/xlink"
        viewBox="440 440 620 620"
        :class="className"
        v-bind="$attrs"
        preserveAspectRatio="xMidYMid meet"
    >
        <defs>
            <clipPath id="sonarya-icon-c1">
                <path d="M 450 480 L 820 480 L 820 843 L 450 843 Z M 450 480" clip-rule="nonzero" />
            </clipPath>
            <clipPath id="sonarya-icon-c2">
                <path
                    d="M 819.226562 480.675781 C 819.394531 505.238281 816.523438 586.367188 776.652344 665.585938 C 764.078125 690.503906 747.867188 715.253906 726.894531 737.992188 C 672.574219 796.902344 586.414062 842.253906 450 842.253906 L 450 703.847656 C 550.609375 703.847656 623.859375 688.433594 677.195312 665.585938 C 696.1875 657.433594 712.675781 648.359375 726.941406 638.6875 C 810.859375 581.789062 818.890625 504.6875 819.226562 480.675781 Z M 819.226562 480.675781"
                    clip-rule="nonzero"
                />
            </clipPath>
            <linearGradient
                x1="-0.0000122125"
                gradientTransform="matrix(1.535312, 0, 0, 1.535312, 450.000013, 473.182089)"
                y1="122.636002"
                x2="240.599996"
                gradientUnits="userSpaceOnUse"
                y2="122.636002"
                id="sonarya-icon-g1"
            >
                <stop stop-opacity="1" stop-color="#f4f4f4" offset="0" />
                <stop stop-opacity="1" stop-color="#b0b0b0" offset="0.3" />
                <stop stop-opacity="1" stop-color="#505050" offset="0.7" />
                <stop stop-opacity="1" stop-color="#000000" offset="1" />
            </linearGradient>
            <clipPath id="sonarya-icon-c3">
                <path d="M 680 657 L 1050 657 L 1050 1020 L 680 1020 Z M 680 657" clip-rule="nonzero" />
            </clipPath>
            <clipPath id="sonarya-icon-c4">
                <path
                    d="M 680.710938 1019.292969 C 680.542969 994.726562 683.414062 913.601562 723.285156 834.378906 C 735.859375 809.460938 752.074219 784.710938 773.046875 761.972656 C 827.363281 703.0625 913.527344 657.710938 1049.953125 657.710938 L 1049.953125 796.121094 C 949.34375 796.121094 876.09375 811.535156 822.757812 834.378906 C 803.765625 842.53125 787.277344 851.605469 773.015625 861.277344 C 689.09375 918.175781 681.0625 995.296875 680.710938 1019.292969 Z M 680.710938 1019.292969"
                    clip-rule="nonzero"
                />
            </clipPath>
            <linearGradient
                x1="167.432007"
                gradientTransform="matrix(-1.535312, 0, 0, -1.535312, 1307.014255, 780.514715)"
                y1="-37.771"
                x2="408.042021"
                gradientUnits="userSpaceOnUse"
                y2="-37.771"
                id="sonarya-icon-g2"
            >
                <stop stop-opacity="1" stop-color="#f4f4f4" offset="0" />
                <stop stop-opacity="1" stop-color="#b0b0b0" offset="0.3" />
                <stop stop-opacity="1" stop-color="#505050" offset="0.7" />
                <stop stop-opacity="1" stop-color="#000000" offset="1" />
            </linearGradient>
        </defs>
        <path
            fill="#000000"
            d="M 680.679688 671.789062 L 680.679688 473.183594 L 819.257812 473.183594 C 819.257812 473.183594 821.117188 566.144531 785.679688 603.589844 C 750.246094 641.039062 680.679688 671.789062 680.679688 671.789062 Z M 680.679688 671.789062"
            fill-opacity="1"
            fill-rule="nonzero"
        />
        <g clip-path="url(#sonarya-icon-c1)">
            <g clip-path="url(#sonarya-icon-c2)">
                <path
                    fill="url(#sonarya-icon-g1)"
                    d="M 450 480.675781 L 450 842.253906 L 819.394531 842.253906 L 819.394531 480.675781 Z M 450 480.675781"
                    fill-rule="nonzero"
                />
            </g>
        </g>
        <path
            fill="#000000"
            d="M 819.273438 828.175781 L 819.273438 1026.785156 L 680.695312 1026.785156 C 680.695312 1026.785156 678.839844 933.820312 714.273438 896.375 C 749.707031 858.945312 819.273438 828.175781 819.273438 828.175781 Z M 819.273438 828.175781"
            fill-opacity="1"
            fill-rule="nonzero"
        />
        <g clip-path="url(#sonarya-icon-c3)">
            <g clip-path="url(#sonarya-icon-c4)">
                <path
                    fill="url(#sonarya-icon-g2)"
                    d="M 1049.953125 1019.292969 L 1049.953125 657.710938 L 680.542969 657.710938 L 680.542969 1019.292969 Z M 1049.953125 1019.292969"
                    fill-rule="nonzero"
                />
            </g>
        </g>
    </svg>
</template>
