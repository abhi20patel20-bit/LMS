<template>
  <Button
    v-bind="attrs"
    :disabled="attrs.disabled || disabled || locked"
    :label="computedLabel"
    @click="handleClick"
  >
    <slot />
  </Button>
</template>

<script setup>
import { computed, ref, useAttrs, watch } from "vue";
import Button from "primevue/button-original";

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
  /**
   * Milliseconds to keep the button locked when no promise is returned.
   * Set to 0 to unlock immediately, or -1 to keep it locked until manually unlocked/rearmed.
   */
  lockDuration: {
    type: Number,
    default: 700,
  },
  /**
   * When this value changes, the button unlocks. Handy for reset after a flow.
   */
  rearmKey: {
    type: [String, Number, Boolean, Date, Object],
    default: null,
  },
  /**
   * Optional label to show while the button is locked.
   */
  lockLabel: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(["click", "locked", "unlocked"]);
const attrs = useAttrs();
const locked = ref(false);
let timerId = null;

const computedLabel = computed(() => {
  if (locked.value && props.lockLabel) {
    return props.lockLabel;
  }
  return attrs.label;
});

const unlock = () => {
  if (locked.value) {
    if (timerId) {
      clearTimeout(timerId);
      timerId = null;
    }
    locked.value = false;
    emit("unlocked");
  }
};

const handleClick = async (event) => {
  if (locked.value || props.disabled || attrs.disabled) return;

  locked.value = true;
  emit("locked");

  // If listener returns a promise, unlock after it settles.
  const results = emit("click", event, unlock);
  const maybePromise = results?.[0];

  if (maybePromise?.finally) {
    await maybePromise.finally(unlock);
  } else if (props.lockDuration >= 0) {
    // Lock for configured duration to prevent rapid double clicks.
    timerId = setTimeout(unlock, props.lockDuration);
  }
};

watch(
  () => props.rearmKey,
  () => unlock()
);

defineExpose({ unlock });
</script>
