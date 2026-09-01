<template>
    <span
        v-bind:style="`background-color: ${hexcolor};color: ${labelColor};`"
        :class="`text-white text-xs mr-2 p-2 font-bold rounded ml-3 ${className}`"
    >
        {{ label }}
    </span>
</template>

<script>
export default {
    props: {
        hexcolor: String,
        label: String,
        className: String,
    },
    data() {
        const hex = this.hexcolor.slice(1); //Bg color in hex, without any prefixing #!

        const rhex = hex.slice(0, 2);
        const ghex = hex.slice(2, 4);
        const bhex = hex.slice(4);

        //break up the color in its RGB components
        const r = parseInt(rhex, 16);
        const g = parseInt(ghex, 16);
        const b = parseInt(bhex, 16);

        let color = '';

        //do simple weighted avarage
        //
        //(This might be overly simplistic as different colors are perceived
        // differently. That is a green of 128 might be brighter than a red of 128.
        // But as long as it's just about picking a white or black text color...)
        if(r + g + b > 382){
            //bright color, use dark font
            color = 'black';
        }else{
            //dark color, use bright font
            color = 'white';
        }

        return {
            labelColor: color,
        }
    },
}
</script>

<style scoped></style>
