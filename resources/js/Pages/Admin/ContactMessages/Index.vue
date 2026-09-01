<template>
  <AppLayout :title="__('Contact Messages')">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Contact Messages') }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('Incoming Messages') }}</h3>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Email') }}</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Phone') }}</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Topic') }}</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="message in messages.data" :key="message.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(message.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ message.first_name }} {{ message.last_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ message.email }}
                  </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ message.phone }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatTopic(message.subject) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(message.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ __(message.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 rtl:space-x-reverse">
                    <button @click="viewMessage(message)" class="text-indigo-600 hover:text-indigo-900">{{ __('View') }}</button>
                    <button @click="deleteMessage(message.id)" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination placeholder -->
          <div class="mt-6 flex justify-center" v-if="messages.links">
             <!-- Simplified pagination for now -->
          </div>
        </div>
      </div>
    </div>

    <!-- Message View Modal -->
    <JetDialogModal :show="viewingMessage" @close="viewingMessage = null">
      <template #title>
        {{ __('Message from') }} {{ viewingMessage?.first_name }}
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="flex justify-between">
            <span class="font-bold">{{ __('From') }}:</span>
            <span>{{ viewingMessage?.email }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-bold">{{ __('Topic') }}:</span>
            <span>{{ formatTopic(viewingMessage?.subject) }}</span>
          </div>
          <hr />
          <div class="mt-4 p-4 bg-gray-50 rounded">
            <p class="whitespace-pre-wrap">{{ viewingMessage?.message }}</p>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700">{{ __('Update Status') }}</label>
            <select v-model="statusForm.status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
              <option value="new">{{ __('New') }}</option>
              <option value="read">{{ __('Read') }}</option>
              <option value="replied">{{ __('Replied') }}</option>
            </select>
          </div>
        </div>
      </template>
      <template #footer>
        <JetSecondaryButton @click="viewingMessage = null">{{ __('Close') }}</JetSecondaryButton>
        <JetButton @click="updateStatus" class="ml-2" :class="{ 'opacity-25': statusForm.processing }" :disabled="statusForm.processing">
          {{ __('Update Status') }}
        </JetButton>
      </template>
    </JetDialogModal>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetButton from '@/Jetstream/Button.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/inertia-vue3'
import { Inertia } from '@inertiajs/inertia'

export default {
  components: {
    AppLayout,
    JetDialogModal,
    JetButton,
    JetSecondaryButton
  },
  props: {
    messages: Object
  },
  setup(props) {
    const page = usePage()
    const viewingMessage = ref(null)
    const statusForm = useForm({
      status: 'new'
    })

    const __ = (key) => {
      return page.props.value.language[key] || key
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString()
    }

    const formatTopic = (topic) => {
      if (!topic) return 'N/A'
      return topic.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    }

    const getStatusClass = (status) => {
      switch (status) {
        case 'new': return 'bg-blue-100 text-blue-800'
        case 'read': return 'bg-yellow-100 text-yellow-800'
        case 'replied': return 'bg-green-100 text-green-800'
        default: return 'bg-gray-100 text-gray-800'
      }
    }

    const viewMessage = (message) => {
      viewingMessage.value = message
      statusForm.status = message.status
    }

    const updateStatus = () => {
      statusForm.patch(route('contactMessages.update', viewingMessage.value.id), {
        onSuccess: () => {
           // We might need to manually update the message in props or rely on the reload
        }
      })
    }

    const deleteMessage = (id) => {
      if (confirm(__('Are you sure you want to delete this message?'))) {
        Inertia.delete(route('contactMessages.destroy', id))
      }
    }

    return {
      __,
      viewingMessage,
      statusForm,
      formatDate,
      formatTopic,
      getStatusClass,
      viewMessage,
      updateStatus,
      deleteMessage
    }
  }
}
</script>
