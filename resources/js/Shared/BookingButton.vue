<template>
    <div>
        <div class="bg-yellow-200 text-yellow-800 relative text-center rounded px-3"
             v-if="booking.status === 0" >
            <div v-if="booking.status === 0 && user.role === 2"
                 class="hidden bg-white border-0 mb-3 block z-50 font-normal leading-normal text-sm max-w-xs text-left no-underline break-words rounded-lg"
                 id="tooltip-example-submitted">
                <div>
                    <div class="bg-white text-gray opacity-75 p-3 mb-0 border border-solid uppercase rounded">
                        move to collected
                    </div>
                </div>
            </div>
            <button
                v-if="user.role === 2"
                @mouseenter="openPopover($event,'tooltip-example-submitted')"
                @mouseleave="openPopover($event,'tooltip-example-submitted')"
                @click="moveToCollected(booking.id)"
                class="p-1 border border-gray-400 absolute -top-3 -right-3 bg-white rounded-full hover:cursor-pointer">
                <img width="12" :src="'/custom/edit.png'"  alt="edit"/>
            </button>
            Submitted
        </div>
        <div class="bg-yellow-200 text-yellow-800 text-center text-md relative rounded px-3"
             v-else-if="booking.status === 1">
            <div v-if="booking.status === 1 && user.role === 2"
                 class="hidden bg-white border-0 mb-3 block z-50 font-normal leading-normal text-sm max-w-xs text-left no-underline break-words rounded-lg"
                 id="tooltip-example-pending">
                <div>
                    <div class="bg-white text-gray opacity-75 p-3 mb-0 border border-solid uppercase rounded">
                        move to collected
                    </div>
                </div>
            </div>
            <button
                v-if="user.role === 2"
                @mouseenter="openPopover($event,'tooltip-example-pending')"
                @mouseleave="openPopover($event,'tooltip-example-pending')"
                @click="moveToCollected(booking.id)"
                class="p-1 border border-gray-400 absolute -top-2 -right-2 bg-white rounded-full hover:cursor-pointer">
                <img width="10" :src="'/custom/edit.png'" alt="edit" />
            </button>
            Pending
        </div>
        <div class="bg-blue-200 text-blue-800 text-center text-md relative rounded px-3"
             v-else-if="booking.status === 2">
            <div v-if="booking.status === 2 && user.role === 1"
                 class="hidden bg-white border-0 mb-3 block z-50 font-normal leading-normal text-sm max-w-xs text-left no-underline break-words rounded-lg"
                 id="tooltip-example-collected">
                <div>
                    <div class="bg-white text-gray opacity-75 p-3 mb-0 border border-solid uppercase rounded">
                        move to received
                    </div>
                </div>
            </div>
            <button
                @mouseenter="openPopover($event,'tooltip-example-collected')"
                @mouseleave="openPopover($event,'tooltip-example-collected')"
                @click="moveToReceived(booking.id)"
                v-if="user.role === 1"
                class="p-1 border border-gray-400 absolute -top-2 -right-2 bg-white rounded-full hover:cursor-pointer">
                <img width="10" :src="'/custom/edit.png'" alt="edit" />
            </button>
            Collected
        </div>
        <div class="bg-purple-200 text-purple-800 text-center text-md relative rounded px-3" v-else-if="booking.status === 3">
            <div v-if="booking.status === 3"
                 class="hidden bg-white border-0 mb-3 block z-50 font-normal leading-normal text-sm max-w-xs text-left no-underline break-words rounded-lg"
                 id="tooltip-example-negative">
                <div>
                    <div class="bg-white text-gray opacity-75 p-3 mb-0 border border-solid uppercase rounded">
                        set the booking result
                    </div>
                </div>
            </div>
            <button
                @mouseenter="openPopover($event,'tooltip-example-negative')"
                @mouseleave="openPopover($event,'tooltip-example-negative')"
                @click="setResult(booking.id)"
                v-if="user.role === 1"
                class="p-1 border border-gray-400 absolute -top-2 -right-2 bg-white rounded-full hover:cursor-pointer">
                <img width="10" :src="'/custom/edit.png'" alt="edit" />
            </button>
            Received
        </div>
        <div class="bg-red-200 text-red text-center relative rounded text-md px-3" v-else-if="booking.status === 4">
            Positive
        </div>
        <div class="bg-emerald-200 text-emerald-800 text-center relative rounded text-md px-3" v-else-if="booking.status === 5">
            Negative
        </div>

        <transition name="modal">
            <modal v-if="showModal" @close="showModal = false">
                <!--
                  you can use custom content here to overwrite
                  default content
                -->
                <div class="modal-mask rounded-2xl">
                    <div class="modal-wrapper m-24 ">
                        <div class="modal-container relative">
                            <span class="absolute top-1 right-1 mt-1 mr-2 cursor-pointer" @click="closeModal()">X</span>

                            <div class="modal-header flex justify-center">
                                <h3 class="text-pcr text-3xl font-bold">Set Booking Result</h3>
                            </div>

                            <div class="modal-body border-b-4 border-state-500 pb-6">
                                <div class="flex justify-around items-stretch py-6 gap-24">
                                    <div class="form-check form-check-inline">
                                        <input v-model="result" class="border border-pcr-mid bg-white checked:bg-pcr-mid text-pcr-mid checked:border-pcr-mid focus:outline-none transition duration-200 mt-1 align-top float-left mr-2 cursor-pointer" type="radio" name="inlineRadioOptions" id="inlineRadio1" :value="4">
                                        <label class="form-check-label inline-block text-gray-700" for="inlineRadio1">Positive</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input v-model="result" class="border border-pcr-mid bg-white checked:bg-pcr-mid text-pcr-mid checked:border-pcr-mid focus:outline-none transition duration-200 mt-1 align-top float-left mr-2 cursor-pointer" type="radio" name="inlineRadioOptions" id="inlineRadio2" :value="5">
                                        <label class="form-check-label inline-block text-gray-700" for="inlineRadio2">Negative</label>
                                    </div>
                                </div>
                                <form class="flex justify-center items-center space-x-6">
                                    <div class="shrink-0">
                                        <img class="h-16 w-16 object-cover" :src="'/custom/pdf.png'" alt="Current profile photo" />
                                    </div>
                                    <label class="block">
                                        <span class="sr-only text-pcr-mid">Choose profile photo</span>
                                        <input type="file" class="block w-full text-sm text-slate-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-full file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-teal-50 file:text-teal-700
                                              hover:file:bg-teal-100
                                              rounded-2xl
                                              outline outline-1 outline-pcr-light
                                              focus:outline focus:outline-1 focus:outline-pcr-mid
                                            "
                                               @input="onSelectedFiles"
                                               accept="application/pdf"
                                        />
                                    </label>
                                </form>
                                <div class="flex justify-center mt-6" v-if="resultError">
                                    <div class="bg-rose-500 text-white text-center rounded-md w-3/4">
                                        <vue-feather :type="'alert-triangle'" stroke-width="2" class="h-4 w-8 place-self-center inline-block"></vue-feather>
                                        Please fill the result information
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer mt-12 border">
                                <button v-if="this.resultLoading" class="px-4 py-1 hover:bg-pcr bg-pcr-mid text-white rounded-lg" disabled>
                                    <svg class="mr-2 w-4 h-4 text-gray-200 animate-spin fill-pcr" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"></path>
                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"></path>
                                    </svg>
                                    Uploading ...
                                </button>
                                <button v-else class="px-4 py-1 hover:bg-pcr bg-pcr-mid text-white rounded-lg" @click="confirmResult()">
                                    Confirm
                                </button>
                                <button class="modal-default-button px-2 py-1 hover:bg-rose-600 bg-rose-400 text-white rounded-lg" @click="closeModal()">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </modal>
        </transition>
    </div>
</template>

<script>

import { computed } from 'vue'
import {usePage} from "@inertiajs/inertia-vue3";
import {createPopper} from "@popperjs/core";
import Button from "@/Jetstream/Button";
import axios from "axios";

export default {
    name: 'BookingButton',
    components: {Button},
    data() {
      return {
          showModal: false,
          bookingId: null,
          resultFile: null,
          result: null,
          resultError: false,
          resultLoading: false
      }
    },
    props:{
        booking: Object,
        labs: Array,
        page: String,
        showPage: {
            type: Boolean,
            default: false
        }
    },
    setup() {
        const user = computed(() => usePage().props.value.auth.user)
        return { user }
    },
    methods:{
        openPopover(event, tooltipID) {
            let element = event.target;
            while (element.nodeName !== "BUTTON") {
                element = element.parentNode;
            }
            createPopper(element, document.getElementById(tooltipID), {
                placement: 'top'
            });
            document.getElementById(tooltipID).classList.toggle("hidden");
        },
        async moveToCollected(id) {

            let labsSelect = [];
            this.labs.forEach((item) => {
                labsSelect[item.id] = item.name;
            });

            await this.$swal({
                title: 'Are you sure?',
                text: "Please select a laboratory to assign to!",
                input: 'select',
                inputOptions: labsSelect,
                inputPlaceholder: 'Select a laboratory',
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it status!',
                width: 400,
                padding: '1em',
                color: '#014758',
                inputValidator: (value) => {
                    return new Promise((resolve) => {
                        if (value > 0) {
                            resolve()
                        } else {
                            resolve('You need to select laboratory')
                        }
                    })
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    this.showSuccessMessage('Your booking status has been changed.');
                    if (this.showPage)
                        this.$inertia.get(route('portal.bookings.lab.change', id), {'lab' : result.value, 'page': this.page, 'show': true})
                    else
                        this.$inertia.get(route('portal.bookings.lab.change', id), {'lab' : result.value, 'page': this.page})
                }
            })
        },
        async moveToReceived(id) {
            await this.$swal({
                title: 'Are you sure?',
                text: "Please select the laboratory kit!",
                input: 'select',
                inputOptions: {
                    1: 'Kits from your laboratory',
                    2: 'kits from Laboshop'
                },
                inputPlaceholder: 'Select a laboratory kit',
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change the kit status!',
                width: 400,
                padding: '1em',
                color: '#014758',
                inputValidator: (value) => {
                    return new Promise((resolve) => {
                        if (value > 0) {
                            resolve()
                        } else {
                            resolve('You need to select kit status')
                        }
                    })
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    this.showSuccessMessage('Booking kit status has been changed')
                    if (this.showPage)
                        this.$inertia.get(route('portal.bookings.kit.change', id), {'kit_status' : result.value, 'page': this.page, 'show': true})
                    else
                        this.$inertia.get(route('portal.bookings.kit.change', id), {'kit_status' : result.value, 'page': this.page})
                }
            })
        },
        setResult(id) {
            this.showModal = true;
            this.bookingId = id;
        },
        closeModal(){
            this.showModal = false;
            this.bookingId = null;
            this.resultError = false;
            this.resultFile = null;
            this.result = null;
            this.resultLoading = false;
        },
        confirmResult(){
            if (this.resultFile === null || this.result === null){
                this.resultError = true;
            }else{
                this.resultLoading = true;
                let formData = new FormData;
                formData.append('file', this.resultFile)
                formData.append('result', this.result)
                formData.append('page', this.page)
                axios.post(this.route('portal.bookings.result.set', this.bookingId), formData,{
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    preserveScroll: true
                }).then((result) => {
                    if (result.status === 200){
                        this.showSuccessMessage('Booking result set successfully')
                        if (this.showPage)
                            this.$inertia.get(route('portal.bookings.show', this.bookingId))
                        else
                            this.$inertia.get(route('portal.bookings.index'), {'page': this.page})
                    }else{
                        this.showErrorMessage('something went wrong')
                    }
                    this.resultLoading = false;
                    this.showModal = false;
                    this.bookingId = null;
                    this.resultError = false;
                    this.resultFile = null;
                    this.result = null;
                } );
            }
        },
        onSelectedFiles($event){
            this.resultFile = $event.target.files[0];
        },
        showSuccessMessage(msg){
            return this.$swal.fire({
                    html: '<p class="text-white pt-5 font-extrabold">'+msg+'</p>',
                    icon: 'success',
                    iconColor: '#FFFFFF',
                    width: 400,
                    showConfirmButton: false,
                    padding: '1em',
                    toast: true,
                    position: 'bottom-end',
                    color: '#FFFFFF',
                    background: '#34d399',
                    timer: 2000,
                    timerProgressBar: true,
                },
            )
        },
        showErrorMessage(msg){
            return this.$swal.fire({
                    html: '<p class="text-white pt-5 font-extrabold">'+msg+'</p>',
                    icon: 'warning',
                    iconColor: '#FFFFFF',
                    width: 400,
                    showConfirmButton: false,
                    padding: '1em',
                    toast: true,
                    position: 'bottom-end',
                    color: '#FFFFFF',
                    background: '#e96e83',
                    timer: 2000,
                    timerProgressBar: true,
                },
            )
        }

    },
}
</script>
<style scoped>
.modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: table;
    transition: opacity 0.3s ease;
}

.modal-wrapper {
    display: table-cell;
    vertical-align: middle;
}

.modal-container {
    width: 30%;
    margin: 0px auto;
    padding: 20px 30px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    transition: all 0.3s ease;
    font-family: Helvetica, Arial, sans-serif;
}

.modal-header h3 {
    margin-top: 20px;
}

.modal-body {
    margin: 20px 0;
}

.modal-default-button {
    float: right;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
}

</style>
