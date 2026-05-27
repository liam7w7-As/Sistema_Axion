<template>
    <!-- NOTA 13 ERS: Calculadora auxiliar. No afecta caja, inventario, saldos ni reportes. -->
    <el-card class="shadow-sm" body-class="!p-3">
        <template #header>
            <div class="flex items-center justify-between">
                <span class="font-semibold text-gray-700 text-sm">🧮 Calculadora Auxiliar</span>
                <el-button type="info" size="small" plain @click="limpiar">C</el-button>
            </div>
        </template>

        <div class="text-right mb-2 bg-gray-100 rounded-md p-2 min-h-[32px]">
            <span class="text-lg font-mono font-bold text-gray-900">{{ pantalla || '0' }}</span>
        </div>

        <div class="grid grid-cols-4 gap-1">
            <el-button v-for="btn in botones" :key="btn" size="small" 
                :type="esOperador(btn) ? 'primary' : (btn === '=' ? 'success' : 'default')" 
                class="!font-bold"
                @click="presionar(btn)"
            >
                {{ btn }}
            </el-button>
        </div>
    </el-card>
</template>

<script setup>
/**
 * NOTA 13 ERS: Calculadora auxiliar.
 * Este componente es 100% frontend. No envía datos al backend.
 * No afecta useForm, caja, inventario, saldos ni reportes.
 */
import { ref } from 'vue';

const pantalla = ref('');
const botones = ['7','8','9','÷','4','5','6','×','1','2','3','-','0','.','=','+'];

const esOperador = (btn) => ['÷','×','-','+'].includes(btn);

const presionar = (btn) => {
    if (btn === '=') {
        try {
            // Reemplazar símbolos visuales por operadores JS
            const expresion = pantalla.value
                .replace(/÷/g, '/')
                .replace(/×/g, '*');
            pantalla.value = String(parseFloat(eval(expresion).toFixed(2)));
        } catch {
            pantalla.value = 'Error';
        }
    } else {
        if (pantalla.value === 'Error') pantalla.value = '';
        pantalla.value += btn;
    }
};

const limpiar = () => {
    pantalla.value = '';
};
</script>
