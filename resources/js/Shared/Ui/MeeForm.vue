<template>
  <div>
    <form class="w-full" @submit.prevent="handleSubmit">
      <fieldset>
          <slot />
      </fieldset>
        <jet-button>
            حفظ
        </jet-button>
    </form>
  </div>
</template>

<script>
import { ManageActionSection } from '@/Shared/Common'
import JetButton from '@/Jetstream/Button.vue'

const components = { ManageActionSection, JetButton }

export default {
  name: 'MeeForm',

  components,

  props: {
    formTitle: {
      type: String,
      default: null,
      require: false,
    },
    btnTitle: {
      type: String,
      default: null,
      require: true,
    },
  },

  methods: {
    handleSubmit() {
      this.$emit('submitted')
    },
  },
}
</script>
