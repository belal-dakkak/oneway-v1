<template>
  <div class="form-control max-w-sm mb-8">
      <div class="form-control flex-row space-x-4">
          <label v-for="(option, index) in options" :key="index" :value="index" class="cursor-pointer label justify-start items-end space-x-2">
              <span class="label-text">{{ option.title }}</span>
              <input type="radio" class="radio text-pcr-mid"
                     @input="update($event.target.value)"
                     :checked="modelValue === option.value"
                     :value="option.value"
                     v-bind:name="name"
                     value="'{{option.value}}'"/>
          </label>
      </div>
  </div>
</template>

<script>
export default {
  name: 'MeeRadio',
    data(){
    },
  props: {
      label: {
          type: String,
          required: false,
      },
      name: {
          type: String,
          required: true,
      },
      options: {
          type: Array,
          required: true,
      },
      modelValue: { required: true, type: [Number, String] },
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
