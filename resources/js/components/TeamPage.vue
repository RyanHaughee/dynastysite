<template>
    <div v-if="team" class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card" style="margin-bottom:10px; cursor:pointer">
                    <div class="card-header">
                        <h4 style="display: inline; margin-left: 5px">{{ team.team_name }}</h4>
                    </div>

                    <div class="card-body">
                        <div class="container">
                            <div class="row">
                                <div class="col-sm-4" style="text-align:center;margin: auto;">
                                    <img v-if="team.team_logo" :src="team.team_logo" style="max-height:200px"/>
                                </div>
                                <div class="col-sm-4" style="max-height:250px">
                                    <Doughnut
                                        id="posValueChart"
                                        :options="chartOptions"
                                        :data="chartData"
                                        v-if="chartReady"
                                    />
                                </div>
                                <div class="col-sm-4" style="text-align:center">
                                    <h4>Team History</h4>
                                    <b>All Time Record:</b> {{ team.alltime_wins }} - {{ team.alltime_losses }} ({{ Math.round((team.alltime_wins / (team.alltime_wins + team.alltime_losses))*1000)/10}}%)
                                </div>
                            </div>
                            <div class="row" style="margin-top:50px">
                                <div class="col-sm-12">
                                    <div class="container">
                                        <div class="row">
                                            <div v-for="(player_arr, pos) in team.players" :key="player_arr.id" class="col-sm-3">
                                                <div v-for="player in player_arr" :style="{ color: positions[pos] }" :key="player.id" style="font-size:16px; font-weight:500">
                                                    {{ player.full_name }} 
                                                    <font-awesome-icon v-if="(player.player_value > 7000)" :icon="['fas', 'gem']" />
                                                    <font-awesome-icon v-else-if="(player.player_value > 5000)" :icon="['fas', 'star']" />
                                                    <font-awesome-icon v-else-if="(player.player_value < 1500)" :icon="['fas', 'trash-can']" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import axios from 'axios'
    import { Doughnut } from 'vue-chartjs';
    import Chart from 'chart.js/auto';

    // this is must have
    import { library } from '@fortawesome/fontawesome-svg-core';
    import {  } from "@fortawesome/free-regular-svg-icons";
    import { faStar, faGem, faTrashCan } from "@fortawesome/free-solid-svg-icons";
    library.add(faGem, faStar, faTrashCan)

    export default {
        components: {Doughnut},
        props: ['team_id'],
        watch: { 
            team_id: function() {
                this.chartReady = false;
                this.getExpandedTeamData();
            }
        },
        data() {
            return {
                team: null,
                chartReady: false,
                positions: { 
                    'QB': '#36A2EB', 
                    'RB': '#FF6384', 
                    'WR': '#FF9F40', 
                    'TE': '#FFCD56'
                },
                chartData: {
                    labels: [ 'QB', 'RB', 'WR', 'TE', '2023', '2024', '2025'],
                    datasets: [ { 
                        data: [],
                        circumference: 365
                    } ]
                },
                chartOptions: {
                    responsive: true,
                }
            }
        },
        mounted() {
            this.getExpandedTeamData();
        },
        methods: {
            async getExpandedTeamData() {
                let response = await axios.get('/team/value/expanded/'+this.team_id);
                if (response && response.data && response.data.success)
                {
                    this.team = response.data.team;
                    this.team.players = JSON.parse(response.data.team.pos_array);
                    this.team.value = JSON.parse(response.data.team.value);
                    this.setTeamValue();
                } else {
                    console.log(response)
                }
            },
            setTeamValue(){
                let valueArr = [];
                console.log(this.team.value.draft);
                let circumference = 365 * (Math.round(this.team.value.total.value) / 130000);
                valueArr.push(Math.round(this.team.value.QB.value));
                valueArr.push(Math.round(this.team.value.RB.value));
                valueArr.push(Math.round(this.team.value.WR.value));
                valueArr.push(Math.round(this.team.value.TE.value));
                if (this.team.value.draft["2023"])
                {
                    valueArr.push(Math.round(this.team.value.draft["2023"].value));
                } else {
                    valueArr.push(Math.round(0));
                }
                if (this.team.value.draft["2024"])
                {
                    valueArr.push(Math.round(this.team.value.draft["2024"].value));
                } else {
                    valueArr.push(Math.round(0));
                }
                if (this.team.value.draft["2025"])
                {
                    valueArr.push(Math.round(this.team.value.draft["2025"].value));
                } else {
                    valueArr.push(Math.round(0));
                }
                this.chartData.datasets[0].data = valueArr;
                this.chartData.datasets[0].circumference = circumference;
                this.chartReady = true;
            }
        }
    }
</script>
