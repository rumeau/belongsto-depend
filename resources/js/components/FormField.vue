<template>
    <default-field :field="field" :errors="errors">
        <template slot="field">
            <div class="flex items-center mb-3">
                <search-input
                    v-if="isSearchable && !isLocked && !isReadonly"
                    :data-testid="`${field.resourceName}-search-input`"
                    @input="performSearch"
                    @clear="clearSelection"
                    @selected="selectResource"
                    :error="hasError"
                    :value="selectedResource"
                    :data="availableResources"
                    :clearable="field.nullable"
                    trackBy="value"
                    searchBy="display"
                    class="w-full"
                >
                    <div slot="default" v-if="selectedResource" class="flex items-center">
                        <div v-if="selectedResource.avatar" class="mr-3">
                            <img
                                :src="selectedResource.avatar"
                                class="w-8 h-8 rounded-full block"
                            />
                        </div>

                        {{ selectedResource.display }}
                    </div>

                    <div
                        slot="option"
                        slot-scope="{ option, selected }"
                        class="flex items-center"
                    >
                        <div v-if="option.avatar" class="mr-3">
                            <img :src="option.avatar" class="w-8 h-8 rounded-full block" />
                        </div>

                        {{ option.display }}
                    </div>
                </search-input>

                <select-control
                    v-if="!isSearchable || isLocked || isReadonly"
                    class="form-control form-select w-full"
                    :class="{ 'border-danger': hasError }"
                    :data-testid="`${field.resourceName}-select`"
                    :dusk="field.attribute"
                    @change="selectResourceFromSelectControl"
                    :disabled="isLocked || isReadonly"
                    :options="availableResources"
                    :value="selectedResourceId"
                    :selected="selectedResourceId"
                    label="display"
                >
                    <option value="" selected :disabled="!field.nullable">{{
                        placeholder
                        }}</option>
                </select-control>

                <create-relation-button
                    v-if="canShowNewRelationModal"
                    @click="openRelationModal"
                    class="ml-1"
                />
            </div>

            <portal to="modals" transition="fade-transition">
                <create-relation-modal
                    v-if="relationModalOpen && canShowNewRelationModal"
                    @set-resource="handleSetResource"
                    @cancelled-create="closeRelationModal"
                    :resource-name="field.resourceName"
                    :resource-id="resourceId"
                    :via-relationship="viaRelationship"
                    :via-resource="viaResource"
                    :via-resource-id="viaResourceId"
                    width="800"
                />
            </portal>

            <!-- Trashed State -->
            <div v-if="shouldShowTrashed">
                <checkbox-with-label
                    :dusk="`${field.resourceName}-with-trashed-checkbox`"
                    :checked="withTrashed"
                    @input="toggleWithTrashed"
                >
                    {{ __('With Trashed') }}
                </checkbox-with-label>
            </div>
        </template>
    </default-field>
</template>

<script>
    import BelongsTo from '../../../../../nova/resources/js/components/Form/BelongsToField';
    import _ from 'lodash'

    export default {
        extends: BelongsTo,

        data () {
            return {
                skipInit: false,
                dependsId: null
            }
        },

        computed : {
            /**
             * Get the query params for getting available resources
             */
            queryParams() {
                return {
                    params: {
                        current: this.selectedResourceId,
                        first: this.initializingWithExistingResource,
                        search: this.search,
                        withTrashed: this.withTrashed,
                        resourceId: this.resourceId,
                        viaResource: this.viaResource,
                        viaResourceId: this.viaResourceId,
                        viaRelationship: this.viaRelationship,
                        dependsOn: this.field.dependsOn,
                        dependsId: this.dependsId,
                        foreignKeyName: this.field.foreignKeyName,
                    },
                }
            }
        },

        created() {
            if (!_.isEmpty(this.field.dependsOn)) {
                this.skipInit = true

                Nova.$on("rumeau-belongsto-depend-" + this.field.dependsOn, async dependsOnValue => {

                    if (dependsOnValue && dependsOnValue.value) {
                        this.dependsId = dependsOnValue.value

                        this.initializeComponent()
                    }
                });
            } else if (!_.isEmpty(this.selectedResourceId)) {
                Nova.$emit("rumeau-belongsto-depend-" + this.field.attribute.toLowerCase(), {
                    value: this.selectedResourceId,
                    field: this.field
                });
            }
        },

        methods: {
            initializeComponent() {
                if (this.skipInit) {
                    this.skipInit = false
                    return;
                }

                this.withTrashed = false

                // If a user is editing an existing resource with this relation
                // we'll have a belongsToId on the field, and we should prefill
                // that resource in this field
                if (this.editingExistingResource) {
                    this.initializingWithExistingResource = true
                    this.selectedResourceId = this.field.belongsToId
                }

                // If the user is creating this resource via a related resource's index
                // page we'll have a viaResource and viaResourceId in the params and
                // should prefill the resource in this field with that information
                if (this.creatingViaRelatedResource) {
                    this.initializingWithExistingResource = true
                    this.selectedResourceId = this.viaResourceId
                }

                if (this.shouldSelectInitialResource && !this.isSearchable) {
                    // If we should select the initial resource but the field is not
                    // searchable we should load all of the available resources into the
                    // field first and select the initial option
                    this.initializingWithExistingResource = false
                    this.getAvailableResources().then(() => this.selectInitialResource())
                } else if (this.shouldSelectInitialResource && this.isSearchable) {
                    // If we should select the initial resource and the field is
                    // searchable, we won't load all the resources but we will select
                    // the initial option
                    // this.selectedResourceId = this.viaResourceId
                    this.getAvailableResources().then(() => this.selectInitialResource())
                } else if (!this.shouldSelectInitialResource && !this.isSearchable) {
                    // If we don't need to select an initial resource because the user
                    // came to create a resource directly and there's no parent resource,
                    // and the field is searchable we'll just load all of the resources
                    this.getAvailableResources()
                }

                this.determineIfSoftDeletes()

                this.field.fill = this.fill
            },

            /**
             * Select a resource using the <select> control
             */
            selectResourceFromSelectControl(e) {
                Nova.$emit("rumeau-belongsto-depend-" + this.field.attribute.toLowerCase(), {
                    value: e.target.value,
                    field: this.field
                });

                this.selectedResourceId = e.target.value
                this.selectInitialResource(true)
            },

            /**
             * Select the initial selected resource
             */
            selectInitialResource(doNotEmit) {
                this.selectedResource = _.find(
                    this.availableResources,
                    r => r.value == this.selectedResourceId
                )

                if (doNotEmit !== true) {
                    Nova.$emit("rumeau-belongsto-depend-" + this.field.attribute.toLowerCase(), {
                        value: this.selectedResourceId,
                        field: this.field
                    });
                }
            }
        }
    }
</script>
