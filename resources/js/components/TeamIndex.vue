<style>
.category {
    margin-bottom:10px;
    cursor:pointer;
    margin:0;
    border-radius: 0;
    display: table;
    height: 50px;
    width: 100%;
    overflow: hidden;
}
.team-card { 
    background-color: #f9f9f9
}
.sub-category {
    max-width:100%; 
}
.team-card:hover { 
    background-color: #36A2EB !important; 
}
.tab-content {
    padding-left:5px;
    display: table-cell; 
    vertical-align: middle;
}
.team-logo {
    max-width:50px
}
.top-margin-30 {
    margin-top:30px
}
.margin-center {
    margin:auto; 
    margin-top:10px
}
.btn-style {
    margin: 1%; 
    margin-top:10px;
    width: 48%
}
</style>

<template>
    <div class="container">
        <div class="row">
            <div class="col-lg-3 order-md-12 justify-content-center">
                <div class="container">
                    <div class="category card" @click="navTabSelected = (navTabSelected == 'team' ? null : 'team')">
                        <div class="tab-content">
                            <font-awesome-icon v-if="navTabSelected == 'team'" :icon="['fas', 'caret-down']" style="margin-right: 5px"/>
                            <font-awesome-icon v-else :icon="['fas', 'caret-right']" style="margin-right: 5px"/> 
                            Teams
                        </div>
                    </div>
                    <span v-if="navTabSelected == 'team'">
                        <div v-for="team in teams" :key="team.id">
                            <div class="sub-category category card team-card" @click="setExpandedTeamId(team.id)">
                                <div class="tab-content">
                                    <div class="container">
                                        <div class="row">
                                            <img class="team-logo" v-if="team.team_logo" :src="team.team_logo" />
                                            {{ team.team_name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </span>
                    <div class="category card" @click="navTabSelected = (navTabSelected == 'trades' ? null : 'trades')">
                        <div class="tab-content">
                            <font-awesome-icon v-if="navTabSelected == 'trades'" :icon="['fas', 'caret-down']" style="margin-right: 5px"/>
                            <font-awesome-icon v-else :icon="['fas', 'caret-right']" style="margin-right: 5px"/> 
                            Trades
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
            <div class="col-lg-9 order-md-1 justify-content-center ">
                <TeamPage v-if="expandedTeamId && navTabSelected == 'team'" :team_id="expandedTeamId"/>
                <TradePage v-if="navTabSelected == 'trades'" :league_id="league.id"/>
            </div>
        </div>
    </div>
</template>

<script>
    import axios from 'axios'
    import TeamPage from './TeamPage.vue';
    import TradePage from './TradePage.vue';

    // this is must have
    import { library } from '@fortawesome/fontawesome-svg-core';
    import { faCaretRight, faCaretDown } from "@fortawesome/free-solid-svg-icons";
    library.add(faCaretRight, faCaretDown)

    export default {
        components: { TeamPage, TradePage },
        watch: { 
            navTabSelected: function () {
                console.log(this.navTabSelected);
            }
        },
        data() {
            return {
                leagueId: "918238038646636544",
                league: null,
                teams: null,
                expandedTeamId: null,
                newLeagueLoading: false,
                navTabSelected: null
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
