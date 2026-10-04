<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { register } from '@/routes';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Masuk ke Akun Kamu',
        description: 'Masukkan email dan password kamu untuk melanjutkan belajar.',
    },
});

defineProps<{
    status?: string;
    canResetPassword?: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk - StudyHub" />

    <div
        v-if="status"
        class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 p-3 text-center text-sm font-medium text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800"
    >
        {{ status }}
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-5">
        <div class="space-y-4">
            <!-- Email -->
            <div class="space-y-1.5">
                <Label for="email" class="text-sm font-medium text-slate-700 dark:text-slate-300">Email</Label>
                <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    v-focus
                    autocomplete="email"
                    placeholder="nama@email.com"
                    class="h-10 border-slate-200 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-100 focus:border-indigo-600 focus:ring-indigo-600"
                />
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-sm font-medium text-slate-700 dark:text-slate-300">Password</Label>
                    <Link
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline"
                    >
                        Lupa password?
                    </Link>
                </div>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan password"
                    class="h-10 border-slate-200 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-100 focus:border-indigo-600 focus:ring-indigo-600"
                />
                <InputError :message="form.errors.password" />
            </div>

            <!-- Remember me -->
            <div class="flex items-center space-x-2 pt-1">
                <Checkbox
                    id="remember"
                    :checked="form.remember"
                    @update:checked="form.remember = $event"
                    class="data-[state=checked]:bg-indigo-600 data-[state=checked]:border-indigo-600 dark:border-slate-700"
                />
                <Label for="remember" class="text-sm font-normal text-slate-600 dark:text-slate-400 cursor-pointer">
                    Ingat saya
                </Label>
            </div>
        </div>

        <Button
            type="submit"
            class="mt-2 h-11 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-all"
            :disabled="form.processing"
        >
            <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
            <span>{{ form.processing ? 'Memproses...' : 'Masuk' }}</span>
        </Button>

        <div class="text-center text-sm text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
            Belum punya akun?
            <Link
                :href="register()"
                class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline ml-1"
            >
                Daftar
            </Link>
        </div>
    </form>
</template>
