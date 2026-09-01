<template>
  <div class="form-control max-w-sm mb-8">
      <div class="form-control flex-row space-x-4">
          <label class="cursor-pointer label justify-start items-end space-x-2">
              <span class="label-text">Active</span>
              <input type="radio" name="status" class="radio text-pcr-mid"
                     @input="update($event.target.value)"
                     :checked="modelValue == 1"
                     checked="{{modelValue == 1 ? 'checked' : ''}}"
                     value="1"/>
          </label>
          <label class="cursor-pointer label justify-start items-end space-x-2">
              <span class="label-text">Inactive</span>
              <input type="radio" name="status" class="radio text-pcr-mid"
                     @input="update($event.target.value)"
                     :checked="modelValue == 0"
                     checked="{{modelValue == 0 ? 'checked' : ''}}"
                     value="0"/>
          </label>
      </div>
  </div>
</template>

<script>
export default {
    name: 'MeeStatus',
    props: {
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
        }
    },
}
</script>
