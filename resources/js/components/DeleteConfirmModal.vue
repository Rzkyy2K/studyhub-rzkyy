<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { AlertTriangle, Trash2 } from '@lucide/vue';

interface Props {
    open: boolean;
    title?: string;
    description?: string;
    itemName?: string;
    confirmText?: string;
    cancelText?: string;
    isDestructive?: boolean;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Konfirmasi Hapus',
    description: 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
    itemName: '',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal',
    isDestructive: true,
    loading: false,
});

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

const onOpenChange = (value: boolean) => {
    emit('update:open', value);
    if (!value) {
        emit('cancel');
    }
};

const handleCancel = () => {
    emit('update:open', false);
    emit('cancel');
};

const handleConfirm = () => {
    emit('confirm');
};
</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl">
            <DialogHeader class="space-y-3">
                <div
                    class="flex size-11 items-center justify-center rounded-full mx-auto sm:mx-0 transition-transform"
                    :class="isDestructive ? 'bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400' : 'bg-amber-100 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400'"
                >
                    <Trash2 v-if="isDestructive" class="size-5" />
                    <AlertTriangle v-else class="size-5" />
                </div>
                <div class="space-y-1.5 text-center sm:text-left">
                    <DialogTitle class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100">
                        {{ title }}
                    </DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        <slot name="description">
                            <span>{{ description }}</span>
                            <span v-if="itemName" class="block mt-1 font-semibold text-slate-800 dark:text-slate-200">
                                "{{ itemName }}"
                            </span>
                        </slot>
                    </DialogDescription>
                </div>
            </DialogHeader>

            <DialogFooter class="pt-4 sm:justify-end gap-2 flex-col-reverse sm:flex-row">
                <Button
                    type="button"
                    variant="outline"
                    @click="handleCancel"
                    :disabled="loading"
                    class="h-9.5 text-xs text-slate-600 dark:text-slate-400 dark:border-slate-700 dark:hover:bg-slate-800"
                >
                    {{ cancelText }}
                </Button>
                <Button
                    type="button"
                    @click="handleConfirm"
                    :disabled="loading"
                    class="h-9.5 text-xs font-semibold text-white transition-all shadow-sm"
                    :class="isDestructive ? 'bg-rose-600 hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500' : 'bg-amber-600 hover:bg-amber-700 text-white'"
                >
                    <span>{{ loading ? 'Memproses...' : confirmText }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
