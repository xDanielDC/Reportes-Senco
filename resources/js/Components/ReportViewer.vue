<template>
    <div>
        <PowerBIReportEmbed :embed-config="embedConfig" css-class-name="h-screen">
        </PowerBIReportEmbed>
    </div>
</template>

<script>
import {PowerBIReportEmbed} from 'powerbi-client-vue-js';
import {models} from "powerbi-client";
export default {
    components: {
        PowerBIReportEmbed
    },

    props: {
        report: {
            type: Object,
            default: {},
            required: true
        },

        fullScreen: {
            type: Boolean,
            default: false
        }
    },

    data(){
        return {
            embedConfig: {
                type: "report",
                id: this.report.report_id,
                embedUrl: this.report.embedUrl,
                accessToken: this.report.token,
                tokenType: models.TokenType.Embed,
                pageView: 'fitToWidth',
                settings: {
                    panes: {
                        filters: {
                            expanded: false,
                            visible: false
                        }
                    },
                    background: models.BackgroundType.Transparent,
                },
                filters: JSON.parse(this.report.filter_array),
            }
        }
    }
}
</script>
