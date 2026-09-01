<template>
  <div class="form-control w-full mb-8">
    <textarea
      ref="input"
      :optional="optional"
      :id="uuid"
      :disabled="readOnly"
      :placeholder="placeholder"
      :value="modelValue"
      @input="update($event.target.value)"
      class="textarea h-24 w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
    />
  </div>
</template>

<script>
export default {
  name: 'MeeTextarea',

  props: {
    modelValue: { required: true, type: [String], default: '' },
    optional: {
      type: Boolean,
      default: false,
    },
    label: {
      type: String,
      required: false,
    },
    placeholder: {
      type: String,
      required: false,
    },
    config: {
      type: Object,
      default: () => ({ type: 'text' }),
    },
    readOnly: {
      type: Boolean,
      default: false,
    },
    uuid: {
      type: Number,
      default: 0,
    },
    validation: {
      type: Object,
      default: () => ({}),
    },

    type: {
      type: String,
      default: 'text',
    },
    validations: {
      type: Object,
      default: () => ({}),
    },
    serverError: {
      type: String,
      required: false,
      default: null,
    },
  },

  methods: {
    update(value) {
      this.$emit('update:modelValue', value)
    },
    focus() {
      this.$refs.input.focus()
    },
  },
}
</script>
