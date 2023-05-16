<style>
.team-logo {
    max-width:50px
}
.team-name {
    font-weight:bold;
    display: inline-block;
    margin-left: 5px;
}
.trade-pieces{margin-top:10px;}
</style>

<template>
    <div class="container">
        <div v-for="trade in trades" :key="trade.id" class="row">
            <div class="col-sm-2">
                <h4>TRADE SCORE:</h4>
                <h4>{{ trade.total_score }}</h4>
            </div>
            <div class="col-sm-5">
                <img class="team-logo" :src="trade.team1.team_logo"/>
                <h5 class="team-name">{{ trade.team1.team_name }} - {{ trade.team1_grade }}</h5>
                <ul class="trade-pieces">
                    <li v-for="piece in trade.team1_details" :key="piece.id">
                        {{ piece }}
                    </li>
                </ul>
            </div>
            <div class="col-sm-5">
                <img class="team-logo" :src="trade.team2.team_logo"/>
                <h5 class="team-name">{{ trade.team2.team_name }} - {{ trade.team2_grade }}</h5>
                <ul class="trade-pieces">
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
