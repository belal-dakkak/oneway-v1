<template>
    <transition v-if="flash.success && visible && !popstate" name="slide-fade">
        <div class="absolute flex bg-emerald-400 text-white max-w-xs w-full z-20 mb-4 mr-4 bottom-0 right-0 rounded-lg shadow p-4">
            <div class="mr-2 bg-white pb-1 pt-2 px-2 rounded-full">
                <vue-feather :type="'check-circle'" stroke-width="2" class="w-6 h-6 text-green-600"></vue-feather>
            </div>
            <div class="flex-1 text-white p-2">
                {{ flash.success }}
            </div>
            <div class="ml-2 pt-2 px-2 rounded-lg cursor-pointer" @click="hideToast()">
                <vue-feather :type="'x'" stroke-width="2" class="w-6 h-6 text-white hover:text-green-800 focus:out line-none focus:text-indigo-600"></vue-feather>
            </div>
        </div>
    </transition>
    <transition name="slide-fade" v-if="flash.success_delete && visible && !popstate">
        <div class="absolute flex bg-rose-400 text-white max-w-xs w-full z-20 mb-4 mr-4 bottom-0 right-0 rounded-lg shadow p-4">
            <div class="mr-2 bg-white pb-1 pt-2 px-2 rounded-full">
                <vue-feather :type="'trash-2'" stroke-width="2" class="w-6 h-6 text-red-600"></vue-feather>
            </div>
            <div class="flex-1 text-white p-2">
                {{ flash.success_delete }}
            </div>
            <div class="ml-2 pt-2 px-2 rounded-lg cursor-pointer" @click="hideToast()">
                <vue-feather :type="'x'" stroke-width="2" class="w-6 h-6 text-white hover:text-red-800 focus:out line-none focus:text-indigo-600"></vue-feather>
            </div>
        </div>
    </transition>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: "toast",
    props: {
        flash: Object,
        popstate: Boolean
    },
    data() {
        return {
            visible: {
                type: Boolean,
                default: false
            },
            timeout: null,
        }
    },
    watch:{
        '$page.props.flash': {
            handler(value) {
                this.visible = true;
            },
            deep: true,
        }
    },
    created() {
        if (this.timeout)
            clearTimeout(this.timeout)

        setTimeout(() =>{
            this.visible = false;
        }, 5000)
    },
    methods: {
        hideToast(){
            this.visible = false;
        }
    }
});
</script>
<style>
.slide-fade-enter-active {
    transition: all .3s ease;
}
.slide-fade-leave-active {
    transition : all .4s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}
.slide-fade-enter, .slide-fade-leave-to {
    transform: translateX(15px);
    opacity: 0;
}
</style>
