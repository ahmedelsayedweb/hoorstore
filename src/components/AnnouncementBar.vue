<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  endDate: {
    type: Date,
    default: () => {
      const date = new Date()
      date.setHours(date.getHours() + 8)
      return date
    }
  }
})

const days = ref('00')
const hours = ref('00')
const minutes = ref('00')
const seconds = ref('00')

let timer = null

const updateCountdown = () => {
  const now = new Date().getTime()
  const end = props.endDate.getTime()
  const distance = end - now

  if (distance < 0) {
    days.value = '00'
    hours.value = '00'
    minutes.value = '00'
    seconds.value = '00'
    return
  }

  days.value = String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, '0')
  hours.value = String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0')
  minutes.value = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0')
  seconds.value = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0')
}

onMounted(() => {
  updateCountdown()
  timer = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <div class="bg-neutral-900 text-white py-2.5 px-5 text-center">
    <div class="flex items-center justify-center gap-8 flex-wrap">
      <span class="text-yellow-600 font-medium text-sm tracking-wider">{{ t('announcement.message') }}</span>
      <div class="flex items-center gap-1">
        <div class="flex flex-col items-center min-w-[30px]">
          <span class="text-lg font-semibold text-white">{{ days }}</span>
          <span class="text-[10px] text-gray-500">{{ t('announcement.days') }}</span>
        </div>
        <span class="text-lg font-semibold text-white mx-0.5 self-start mt-0.5">:</span>
        <div class="flex flex-col items-center min-w-[30px]">
          <span class="text-lg font-semibold text-white">{{ hours }}</span>
          <span class="text-[10px] text-gray-500">{{ t('announcement.hours') }}</span>
        </div>
        <span class="text-lg font-semibold text-white mx-0.5 self-start mt-0.5">:</span>
        <div class="flex flex-col items-center min-w-[30px]">
          <span class="text-lg font-semibold text-white">{{ minutes }}</span>
          <span class="text-[10px] text-gray-500">{{ t('announcement.mins') }}</span>
        </div>
        <span class="text-lg font-semibold text-white mx-0.5 self-start mt-0.5">:</span>
        <div class="flex flex-col items-center min-w-[30px]">
          <span class="text-lg font-semibold text-white">{{ seconds }}</span>
          <span class="text-[10px] text-gray-500">{{ t('announcement.secs') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
