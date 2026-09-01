<template>
  <div class="form-control max-w-sm mb-8">
    <label class="label">
      <span class="label-text font-semibold capitalize">{{ label }}</span>
      <span v-show="optional" class="label-text-alt text-success font-bold">(optinal)</span>
    </label>
    <input
      ref="input"
      :type="type"
      :optional="optional"
      :id="uuid"
      :disabled="readOnly"
      :placeholder="placeholder"
      :value="modelValue"
      @input="update($event.target.value)"
      class="input input-bordered border-info border-opacity-30"
    />
    <label class="label" v-if="serverError">
      <span class="label-text-alt font-bold text-error">{{ serverError }}</span>
    </label>
  </div>
</template>

<script>
export default {
  name: 'TextField',

  props: {
    modelValue: { required: true, type: [String, Number], default: '' | 0 },
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
