<style>
.team-logo {
    max-width:50px
}
.team-grade {
    margin-bottom: 0px;
    color: black;
}
.team-name {
    font-weight:bold;
    display: inline-block;
    margin-left: 5px;
}
.score-container {
    display: flex;
    justify-content: center;
    align-content: center;
    flex-direction: column;
}
.trade-pieces{margin-top:10px; margin-left: 5px}
</style>

<template>
    <div class="container">
        <div v-for="trade in trades" :key="trade.id" class="row">
            <div class="col-sm-2" style="text-align:center">
                <h6>OVERALL VALUE:</h6>
                <h4>{{ trade.total_score }}</h4>
                <hr>
                <p v-if="trade.team1_grade >= 75 && trade.team2_grade >= 75">Both teams got better.</p>
                <p v-else-if="trade.team1_grade < 75 && trade.team2_grade < 75">Both teams got worse.</p>
                <p v-else>One team got more value.</p>
            </div>
            <div class="col-sm-5">
                <div class="container" style="text-align:center">
                    <div class="row">
                        <div class="col-lg-2" style="margin:auto">
                            <img class="team-logo" :src="trade.team1.team_logo"/>
                        </div>
                        <div class="col-lg-8" style="margin:auto">
                            <h5 class="team-name">{{ trade.team1.team_name }}</h5>
                        </div>
                        <div class="col-lg-2 score-container" :style="(trade.team1_grade < 75) ? {'background-color':'rgb(255, 0, 0, '+trade.team1_opacity+')'} : {'background-color':'rgb(0, 128, 0, '+trade.team1_opacity+')'}">
                            <h5 class="team-grade">{{ trade.team1_grade }}</h5>
                        </div>
                    </div>
                </div>
                <hr>
                <ul class="trade-pieces">
                    <li v-for="piece in trade.team1_details" :key="piece.id">
                        {{ piece }}
                    </li>
                </ul>
            </div>
            <div class="col-sm-5">
                <div class="container" style="text-align:center;">
                    <div class="row">
                        <div class="col-lg-2" style="margin:auto">
                            <img class="team-logo" :src="trade.team2.team_logo"/>
                        </div>
                        <div class="col-lg-8" style="margin:auto">
                            <h5 class="team-name">{{ trade.team2.team_name }}</h5>
                        </div>
                        <div class="col-lg-2 score-container" :style="(trade.team2_grade < 75) ? {'background-color':'rgb(255, 0, 0, '+trade.team2_opacity+')'} : {'background-color':'rgb(0, 128, 0, '+trade.team2_opacity+')'}">
                            <h5 class="team-grade">{{ trade.team2_grade }}</h5>
                        </div>
                    </div>
                </div>
                <hr>
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
