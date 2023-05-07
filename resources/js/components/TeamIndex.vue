<style>
.team-card { 
    margin-bottom:10px;
    cursor:pointer;
    margin:0;
    border-radius: 0;
}
.team-card:hover { background-color: #36A2EB !important; }
.team-tab { padding:5px !important;}
.team-logo {max-width:50px}
.top-margin-30 {margin-top:30px}
.margin-center {margin:auto; margin-top:10px}
.btn-style {
    margin: 1%; 
    margin-top:10px;
    width: 48%
}
</style>

<template>
    <div class="container">
        <div class="row">
            <div class="col-md-3 order-md-12 justify-content-center">
                <div class="container">
                    <div v-for="team in teams" :key="team.id" class="row">
                        <div class="card team-card" @click="setExpandedTeamId(team.id)">
                            <div class="card-body team-tab">
                                <div class="container">
                                    <div class="row">
                                        <img class="team-logo" v-if="team.team_logo" :src="team.team_logo" />
                                        {{ team.team_name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row top-margin-30">
                        <label>Sleeper League Id</label>
                        <input v-model="leagueId" type="text" />
                        <button v-if="!newLeagueLoading" class="btn btn-primary btn-style" @click="setupLeague()">Refresh</button>
                        <button v-if="!newLeagueLoading" class="btn btn-secondary btn-style" @click="getLeague()">Load</button>
                        <div v-if="newLeagueLoading" class="spinner-border margin-center" role="status"></div>
                    </div>
                </div>
            </div>
            <div v-if="expandedTeamId" class="col-md-9 order-md-1 justify-content-center ">
                <TeamPage :team_id="expandedTeamId"/>
            </div>
        </div>
    </div>
</template>

<script>
    import axios from 'axios'
    import TeamPage from './TeamPage.vue';

    export default {
        components: { TeamPage },
        data() {
            return {
                leagueId: "918238038646636544",
                league: null,
                teams: null,
                expandedTeamId: null,
                newLeagueLoading: false
            }
        },
        mounted() {
            this.getLeague();
        },
        methods: {
            async getLeague() {
                let response = await axios.get('/league/'+this.leagueId)
                if (response.data && response.data.success && response.data.league)
                {
                    this.league = JSON.parse(response.data.league);
                    this.getTeams();
                }
            },
            async getTeams() {
                let response = await axios.get('/league/get-teams/'+this.league.id)
                if (response.data && response.data.success && response.data.teams)
                {
                    this.teams = JSON.parse(response.data.teams);
                }
            },
            setExpandedTeamId(team_id) {
                this.expandedTeamId = team_id;
            },
            async setupLeague() {
                this.newLeagueLoading = true;
                let response = await axios.get('/setup/league/'+this.leagueId);
                if (response.data && response.data.success)
                {
                    this.newLeagueLoading = false;
                    this.getLeague();
                }
                
            }
        }
    }
</script>
