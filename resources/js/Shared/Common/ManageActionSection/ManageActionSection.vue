<template>
    <div class="my-8">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            <div class="sm:rounded-lg">
                <div class="flex flex-col">
                    <div class="sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="shadow border-b border-gray-200 sm:rounded-lg">
                                <slot />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  name: 'ManageActionSection',

  props: {
    actionTitle: {
      type: String,
      default: null,
      require: false,
    },
  },
}
</script>
