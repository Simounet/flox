<template>
  <input :id="id" type="checkbox" :checked="modelValue" @change="$emit('update:modelValue', $event.target.checked)">
  <label :for="id">{{ label }}</label>
</template>

<script>
  export default {
    name: 'Checkbox',

    props: {
      id: {
        type: String,
        required: true
      },

      label: {
        type: String,
        required: true
      },

      modelValue: {
        type: Boolean,
        required: true
      }
    },

    emits: ['update:modelValue']
  }

</script>
