<template>
        <div class="py-2 col-span-4">
            <div class="max-w-7xl sm:px-6 lg:px-8">
                <div
                    @drop.prevent="onDroppedFiles"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    :class="[dragging ? 'border-indigo-500' :'border-gray-400', 'flex flex-col items-center py-6 px-3 rounded-md border-2 border-dashed']">
                    <svg
                        class="w-12 h-12 text-gray-500"
                        aria-hidden="true" fill="none" stroke="currentColor"
                        viewBox="0 0 48 48">
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>

                    <p class="text-sm text-gray-700">{{ title }}</p>

                    <p class="mb-2 text-gray-700">أو</p>

                    <label class="bg-white px-4 cursor-pointer h-9 inline-flex items-center rounded border border-gray-300 shadow-sm text-sm font-medium text-gray-700 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                        اختر ملف
                        <input
                            ref="files"
                            @input="onSelectedFiles" type="file"
                            class="opacity-0 w-1 cursor-pointer">
                        <input type="hidden" :value="modelValue">
                    </label>

                    <p class="text-xs text-gray-600 mt-4">الحد الأقصى لحجم الملفات المحملة: 512MB</p>
                </div>
            </div>

            <ul class="my-6 bg-white rounded divide-y divide-gray-200 shadow">
                <li v-for="(item, index) in media" :key="index"
                    class="p-3 flex items-center justify-between">
                    <div class="text-sm text-gray-700">{{ item.file.name }}</div>

                    <div v-if="!item.uploaded && !item.error" class="w-40 bg-gray-200 rounded-full h-5 shadow-inner overflow-hidden relative flex items-center justify-center">
                        <div class="inline-block h-full bg-indigo-600 absolute top-0 left-0" :style="`width: ${item.progress}%`"></div>
                        <div class="relative z-10 text-xs font-semibold text-center text-white drop-shadow text-shadow">{{ item.progress }}%</div>
                    </div>

                    <div v-if="item.error" class="text-sm text-red-600">{{ item.error }}</div>
<!--                    <inertia-link href="#" v-if="item.uploaded" class="text-sm text-indigo-600 underline">Delete</inertia-link>-->
                </li>
            </ul>
        </div>
</template>

<script>
import axios from 'axios'

export default {
    props: {
        modelValue: { required: true, type: String, default: '' },
        title: {
            required: true,
            default: 'اسحب الملف هنا'
        },
        name: {
            required: true,
            default: 'image'
        },
        oldValue: {
            required: false,
            default: []
        }
    },
    data() {
        return {
            dragging: false,
            media: [],
            selectedFiles: []
        };
    },
    components: {
    },
    methods: {
        onDroppedFiles($event){
            this.dragging = false
            let files = [...$event.dataTransfer.items]
            .filter(item => item.kind === 'file')
            .map(item => item.getAsFile())

            this.uploadFiles(files)

            this.$refs.files.value = null

        },
        onSelectedFiles($event){
            let files = [...$event.target.files]

            this.uploadFiles(files)

            this.$refs.files.value = null

        },
        uploadFiles(files)
        {
            files.forEach(file => {
                this.media.unshift({
                    file: file,
                    progress: 0,
                    error: null,
                    uploaded: false
                })
            })

            this.media.filter(media => !media.uploaded)
                .forEach(media => {
                    let formData = new FormData;
                    formData.append('file', media.file)
                    axios.post(this.route('media'), formData, {
                        onUploadProgress: (event) => {
                            media.progress = Math.round(event.loaded * 100 / event.total)
                        }
                    })
                        .then((response) => {
                            media.uploaded = true
                            if (!this.selectedFiles.includes(response.data)){
                                this.selectedFiles = [...this.selectedFiles, response.data]
                            }
                            this.$emit('update:modelValue', this.selectedFiles[0])
                        })
                        .catch(error => {
                            media.error = 'فشل عملية رفع الملفات, رجاءً حاول مجدداً'

                            if (error?.response.status === 422){
                                media.error = error.response.data.errors.file[0]
                            }
                        })
                })
        }
    }
};
</script>
