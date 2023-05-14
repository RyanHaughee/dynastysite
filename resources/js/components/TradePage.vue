<style>
</style>

<template>
    <div class="container">
        <div v-for="trade in trades" :key="trade.id" class="row">
            <div class="col-sm-6">
                <h5 style="font-weight:bold">{{ trade.team1.team_name }} - {{ trade.team1_grade }}</h5>
                <ul>
                    <li v-for="piece in trade.team1_details" :key="piece.id">
                        {{ piece }}
                    </li>
                </ul>
                
            </div>
            <div class="col-sm-6">
                <h5 style="font-weight:bold">{{ trade.team2.team_name }} - {{ trade.team2_grade }}</h5>
                <ul>
                    <li v-for="piece in trade.team2_details" :key="piece.id">
                        {{ piece }}
                    </li>
                </ul>
            </div>
            <hr>
        </div>
    </div>
</template>

<script>
    import axios from 'axios'

    // this is must have
    import { library } from '@fortawesome/fontawesome-svg-core';
    import { faStar, faGem, faTrashCan } from "@fortawesome/free-solid-svg-icons";
    library.add(faGem, faStar, faTrashCan)

    export default {
        props: ['league_id'],
        data() {
            return {
                trades: []
            }
        },
        mounted() {
            console.log("mounted");
            this.loadTransactions();
        },
        methods: {
            async loadTransactions(){
                let response = await axios.get('/league/get-trades/'+this.league_id);
                if (response.data && response.data.success && response.data.trades)
                {
                    this.trades = JSON.parse(response.data.trades);
                }
            }
        }
    }
</script>
