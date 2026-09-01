<template>
  <ManageActionSection :actionTitle="tableTitle">
    <div>
      <slot />
    </div>
  </ManageActionSection>
</template>

<script>
import { ManageActionSection } from '@/Shared/Common'

const components = { ManageActionSection }

export default {
  name: 'MeeTable',

  components,

  props: {
    tableTitle: {
      type: String,
      default: null,
      require: false,
    },
  },
}
</script>