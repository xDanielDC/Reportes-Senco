<template>
    <div>
        <section id="container" style="height: 750px"/>
    </div>

</template>

<script>
import { PowerBIReportEmbed } from 'powerbi-client-vue-js';

export default {
    props: {
        accessToken: String,
        groupId: String,
        reportId: String,
        embedUrl: String,
    },

    components: {
        PowerBIReportEmbed
    },

    data() {
        return {
            embedConfig: {
                type: "report",
                id: this.reportId,
                embedUrl: this.embedUrl,
                accessToken: this.token.token,
                tokenType: 'models.TokenType.Embed',
                settings: {
                    panes: {
                        filters: {
                            expanded: false,
                            visible: false
                        }
                    },
                    background: 'models.BackgroundType.Transparent',
                }
            }
        }
    },
}
</script>
